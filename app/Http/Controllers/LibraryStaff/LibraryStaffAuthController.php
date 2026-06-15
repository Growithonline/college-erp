<?php

namespace App\Http\Controllers\LibraryStaff;

use App\Http\Controllers\Controller;
use App\Mail\LibraryStaffAccountLockedMail;
use App\Mail\LibraryStaffOtpMail;
use App\Models\LibraryLoginLog;
use App\Models\LibraryStaff;
use App\Models\LibraryStaffActivityLog;
use App\Models\PlatformSmsSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class LibraryStaffAuthController extends Controller
{
    private const OTP_SESSION_KEY      = 'lib_staff_otp_user_id';
    private const SESSION_TOKEN_KEY    = 'lib_staff_session_token';
    private const LOGIN_IP_KEY         = 'lib_staff_login_ip';
    private const LAST_ACTIVITY_KEY    = 'lib_staff_last_activity';
    private const MAX_OTP_ATTEMPTS     = 3;
    private const LOCK_MINUTES         = 15;
    private const OTP_TTL_MINUTES      = 5;
    private const THROTTLE_SECONDS     = 60;
    private const MAX_OTP_SENDS_PER_HOUR = 3;

    private function guard()
    {
        return Auth::guard('library_staff');
    }

    private function otpCacheKey(int $id): string    { return "lib_staff_otp:{$id}"; }
    private function otpThrottleKey(int $id): string { return "lib_staff_otp_throttle:{$id}"; }
    private function otpHourlyKey(int $id): string   { return "lib_staff_otp_hourly:{$id}"; }

    private function pendingStaff(): ?LibraryStaff
    {
        $id = session(self::OTP_SESSION_KEY);
        return $id ? LibraryStaff::find($id) : null;
    }

    // ── Auth: Login ─────────────────────────────────────────────────

    public function loginForm()
    {
        if ($this->guard()->check()) {
            return redirect()->route('library_staff.dashboard');
        }
        return view('library_staff.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:20']);

        $staff = LibraryStaff::where('phone', trim($request->phone))->first();

        if (!$staff) {
            return back()->withInput($request->only('phone'))
                ->withErrors(['phone' => 'No account found with this mobile number.']);
        }

        if (!$staff->status) {
            return back()->withErrors(['phone' => 'Your account has been deactivated. Please contact the administrator.']);
        }

        if ($staff->isLocked()) {
            $mins = (int) now()->diffInMinutes($staff->locked_until, false);
            return back()->withErrors([
                'phone' => "Your account is temporarily locked. Please try again in {$mins} minute(s).",
            ]);
        }

        $hourlyCount = (int) Cache::get($this->otpHourlyKey($staff->id), 0);
        if ($hourlyCount >= self::MAX_OTP_SENDS_PER_HOUR) {
            return back()->withErrors(['phone' => 'Too many OTP requests. Please try again after some time.']);
        }

        if (Cache::has($this->otpThrottleKey($staff->id))) {
            return back()->withErrors(['phone' => 'Please wait a moment before requesting another OTP.']);
        }

        try {
            $this->sendOtp($staff);
        } catch (Throwable $e) {
            report($e);
            return back()->withInput($request->only('phone'))
                ->withErrors(['phone' => 'Failed to send OTP email. Please try again.']);
        }

        session([self::OTP_SESSION_KEY => $staff->id]);

        LibraryStaffActivityLog::record($staff->id, 'otp_sent', null, null, $request->ip());

        return redirect()->route('library_staff.otp.form')
            ->with('success', 'An OTP has been sent to your registered email address.');
    }

    // ── Auth: OTP ───────────────────────────────────────────────────

    public function showOtpForm()
    {
        $staff = $this->pendingStaff();
        if (!$staff) return redirect()->route('library_staff.login');
        return view('library_staff.auth.otp', compact('staff'));
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $staff = $this->pendingStaff();
        if (!$staff) return redirect()->route('library_staff.login');

        if ($staff->isLocked()) {
            session()->forget(self::OTP_SESSION_KEY);
            return redirect()->route('library_staff.login')
                ->withErrors(['phone' => 'Your account has been locked. Please try again later.']);
        }

        $otpPayload = Cache::get($this->otpCacheKey($staff->id));

        if (!$otpPayload) {
            return back()->withErrors(['otp' => 'OTP has expired. Please go back and request a new one.']);
        }

        if (!Hash::check($request->otp, $otpPayload['hash'] ?? '')) {
            $staff->increment('login_attempts');
            $this->logAttempt($staff, $request, 'failed_otp');

            $remaining = self::MAX_OTP_ATTEMPTS - $staff->login_attempts;

            if ($staff->login_attempts >= self::MAX_OTP_ATTEMPTS) {
                $lockedUntil = now()->addMinutes(self::LOCK_MINUTES);

                $staff->update([
                    'locked_until'   => $lockedUntil,
                    'login_attempts' => 0,
                ]);

                Cache::forget($this->otpCacheKey($staff->id));
                $this->logAttempt($staff, $request, 'locked');
                session()->forget(self::OTP_SESSION_KEY);

                // Alert institute admin
                $this->notifyAdminOfLock($staff, $request->ip(), $lockedUntil->format('d M Y, h:i A'));

                return redirect()->route('library_staff.login')
                    ->withErrors(['phone' => 'Account locked for ' . self::LOCK_MINUTES . ' minutes due to multiple failed OTP attempts. The administrator has been notified.']);
            }

            return back()->withErrors([
                'otp' => "Incorrect OTP. {$remaining} attempt(s) remaining.",
            ]);
        }

        // OTP correct — clear OTP cache
        Cache::forget($this->otpCacheKey($staff->id));
        Cache::forget($this->otpThrottleKey($staff->id));
        Cache::forget($this->otpHourlyKey($staff->id));

        // Generate single-session token
        $sessionToken = Str::random(64);

        $staff->update([
            'login_attempts'  => 0,
            'last_login_at'   => now(),
            'last_login_ip'   => $request->ip(),
            'session_token'   => $sessionToken,
        ]);

        $request->session()->regenerate();
        $this->guard()->login($staff);
        session()->forget(self::OTP_SESSION_KEY);

        // Store security session data
        session([
            self::SESSION_TOKEN_KEY => $sessionToken,
            self::LOGIN_IP_KEY      => $request->ip(),
            self::LAST_ACTIVITY_KEY => time(),
        ]);

        $this->logAttempt($staff, $request, 'success');
        LibraryStaffActivityLog::record($staff->id, 'login', null, 'Successful login.', $request->ip());

        if ($staff->isDualRole()) {
            return redirect()->route('library_staff.portal.select');
        }

        return redirect()->intended(route('library_staff.dashboard'));
    }

    public function resendOtp()
    {
        $staff = $this->pendingStaff();
        if (!$staff) return redirect()->route('library_staff.login');

        if (Cache::has($this->otpThrottleKey($staff->id))) {
            $cooldown = PlatformSmsSetting::current()?->otp_resend_cooldown_seconds ?? self::THROTTLE_SECONDS;
            return back()->withErrors(['otp' => "Please wait {$cooldown} seconds before requesting a new OTP."]);
        }

        $hourlyCount = (int) Cache::get($this->otpHourlyKey($staff->id), 0);
        if ($hourlyCount >= self::MAX_OTP_SENDS_PER_HOUR) {
            return back()->withErrors(['otp' => 'Too many OTP requests this hour. Please try again later.']);
        }

        try {
            $this->sendOtp($staff);
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors(['otp' => 'Failed to resend OTP. Please try again.']);
        }

        return back()->with('success', 'A new OTP has been sent to your registered email address.');
    }

    // ── Portal select (dual role) ───────────────────────────────────

    public function showPortalSelect()
    {
        if (!$this->guard()->check()) return redirect()->route('library_staff.login');
        $staff = $this->guard()->user();
        if (!$staff->isDualRole()) return redirect()->route('library_staff.dashboard');
        return view('library_staff.auth.portal_select', compact('staff'));
    }

    // ── Logout ──────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        $staff = $this->guard()->user();

        if ($staff) {
            // Invalidate single-session token
            $staff->update(['session_token' => null]);
            LibraryStaffActivityLog::record($staff->id, 'logout', null, null, $request->ip());
        }

        if ($staffId = session(self::OTP_SESSION_KEY)) {
            Cache::forget($this->otpCacheKey((int) $staffId));
            Cache::forget($this->otpThrottleKey((int) $staffId));
        }

        $this->guard()->logout();
        session()->forget(self::OTP_SESSION_KEY);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('library_staff.login');
    }

    // ── Dashboard ───────────────────────────────────────────────────

    public function dashboard()
    {
        $staff = $this->guard()->user()->load('institute', 'permissionRecord');
        return view('library_staff.dashboard', compact('staff'));
    }

    // ── Profile ─────────────────────────────────────────────────────

    public function profileForm()
    {
        $staff = $this->guard()->user()->load('permissionRecord', 'loginLogs');
        $loginHistory = $staff->loginLogs()->latest('created_at')->take(10)->get();
        $activityHistory = \App\Models\LibraryStaffActivityLog::where('library_staff_id', $staff->id)
            ->latest('created_at')->take(15)->get();

        return view('library_staff.profile', compact('staff', 'loginHistory', 'activityHistory'));
    }

    public function updateProfile(Request $request)
    {
        $staff = $this->guard()->user();

        $data = $request->validate([
            'name'    => 'required|string|max:100',
            'phone'   => ['required', 'string', 'max:20',
                \Illuminate\Validation\Rule::unique('library_staff', 'phone')->ignore($staff->id)],
            'address' => 'nullable|string|max:300',
        ]);

        $staff->update($data);

        LibraryStaffActivityLog::record(
            $staff->id, 'profile_update',
            null, 'Name/phone/address updated.', $request->ip()
        );

        return back()->with('success', 'Profile updated successfully.');
    }

    // ── Activity log ────────────────────────────────────────────────

    public function activityLog()
    {
        $staff = $this->guard()->user();
        $logs  = \App\Models\LibraryStaffActivityLog::where('library_staff_id', $staff->id)
            ->latest('created_at')
            ->paginate(25);

        return view('library_staff.activity', compact('staff', 'logs'));
    }

    // ── Private helpers ──────────────────────────────────────────────

    private function sendOtp(LibraryStaff $staff): void
    {
        $otp      = (string) random_int(100000, 999999);
        $platform = PlatformSmsSetting::current();
        $expiryMinutes   = $platform?->otp_expiry_minutes ?? self::OTP_TTL_MINUTES;
        $cooldownSeconds = $platform?->otp_resend_cooldown_seconds ?? self::THROTTLE_SECONDS;

        Mail::to($staff->email)->send(new LibraryStaffOtpMail($staff, $otp));

        Cache::put($this->otpCacheKey($staff->id), [
            'hash' => Hash::make($otp),
        ], now()->addMinutes($expiryMinutes));

        Cache::put($this->otpThrottleKey($staff->id), true, now()->addSeconds($cooldownSeconds));

        Cache::put(
            $this->otpHourlyKey($staff->id),
            (int) Cache::get($this->otpHourlyKey($staff->id), 0) + 1,
            now()->addHour()
        );
    }

    private function logAttempt(LibraryStaff $staff, Request $request, string $status): void
    {
        LibraryLoginLog::create([
            'library_staff_id' => $staff->id,
            'ip_address'       => $request->ip(),
            'user_agent'       => substr($request->userAgent() ?? '', 0, 300),
            'status'           => $status,
        ]);
    }

    private function notifyAdminOfLock(LibraryStaff $staff, string $ip, string $lockedUntil): void
    {
        try {
            $adminEmail = $staff->institute?->user?->email ?? null;
            if ($adminEmail) {
                Mail::to($adminEmail)->send(
                    new LibraryStaffAccountLockedMail($staff, $lockedUntil, $ip)
                );
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
