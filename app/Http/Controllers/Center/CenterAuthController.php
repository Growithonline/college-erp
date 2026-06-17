<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Mail\CenterOtpMail;
use App\Models\AcademicSession;
use App\Models\Center;
use App\Models\PlatformSmsSetting;
use App\Services\SmsService;
use App\Models\FeeInvoice;
use App\Models\Notice;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Services\InstituteMailer;
use Throwable;

class CenterAuthController extends Controller
{
    private const OTP_SESSION_KEY = 'center_otp_user_id';

    private function guard()
    {
        return Auth::guard('center');
    }

    private function otpCacheKey(int $centerId): string
    {
        return "center_login_otp:{$centerId}";
    }

    private function otpThrottleKey(int $centerId): string
    {
        return "center_login_otp_throttle:{$centerId}";
    }

    private function pendingCenter(): ?Center
    {
        $centerId = session(self::OTP_SESSION_KEY);
        return $centerId ? Center::find($centerId) : null;
    }

    private function sendOtp(Center $center, bool $remember = false): void
    {
        $otp      = (string) random_int(100000, 999999);
        $platform = PlatformSmsSetting::current();
        $expiryMinutes   = $platform?->otp_expiry_minutes ?? 5;
        $cooldownSeconds = $platform?->otp_resend_cooldown_seconds ?? 30;

        InstituteMailer::send($center->institute_id, $center->email, new CenterOtpMail($center, $otp));

        if ($center->mobile) {
            SmsService::sendOtp($center->mobile, $otp);
        }

        Cache::put($this->otpCacheKey($center->id), [
            'hash'     => Hash::make($otp),
            'remember' => $remember,
            'sms_sent' => (bool) $center->mobile,
        ], now()->addMinutes($expiryMinutes));

        Cache::put($this->otpThrottleKey($center->id), true, now()->addSeconds($cooldownSeconds));
    }

    // ── Login Form ────────────────────────────────────────────────────
    public function loginForm()
    {
        if ($this->guard()->check()) {
            return redirect()->route('center.dashboard');
        }
        return view('center.auth.login');
    }

    // ── Login Submit ──────────────────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $center = Center::where('email', $request->email)->first();

        if (!$center || !Hash::check($request->password, $center->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        if (!$center->status) {
            return back()->withErrors(['email' => 'Your account has been disabled.']);
        }

        try {
            $this->sendOtp($center, $request->boolean('remember'));
        } catch (Throwable $e) {
            report($e);
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Failed to send OTP email. Please try again.']);
        }

        session([self::OTP_SESSION_KEY => $center->id]);

        return redirect()->route('center.otp.form')->with('success', 'OTP has been sent to your email address.');
    }

    // ── OTP Form ──────────────────────────────────────────────────────
    public function showOtpForm()
    {
        $center = $this->pendingCenter();

        if (!$center) {
            return redirect()->route('center.login');
        }

        return view('center.auth.otp', compact('center'));
    }

    // ── OTP Verify ────────────────────────────────────────────────────
    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $center = $this->pendingCenter();

        if (!$center) {
            return redirect()->route('center.login');
        }

        $otpPayload = Cache::get($this->otpCacheKey($center->id));

        if (!$otpPayload) {
            return back()->withErrors(['otp' => 'OTP expired. Please login again.']);
        }

        if (!Hash::check($request->otp, $otpPayload['hash'] ?? '')) {
            return back()->withErrors(['otp' => 'Incorrect OTP.']);
        }

        Cache::forget($this->otpCacheKey($center->id));
        Cache::forget($this->otpThrottleKey($center->id));

        $request->session()->regenerate();
        $this->guard()->login($center, (bool) ($otpPayload['remember'] ?? false));
        session()->forget(self::OTP_SESSION_KEY);

        return redirect()->intended(route('center.dashboard'));
    }

    // ── OTP Resend ────────────────────────────────────────────────────
    public function resendOtp()
    {
        $center = $this->pendingCenter();

        if (!$center) {
            return redirect()->route('center.login');
        }

        if (Cache::has($this->otpThrottleKey($center->id))) {
            $cooldown = PlatformSmsSetting::current()?->otp_resend_cooldown_seconds ?? 30;
            return back()->withErrors(['otp' => "Please wait {$cooldown} seconds before requesting a new OTP."]);
        }

        $remember = (bool) (Cache::get($this->otpCacheKey($center->id))['remember'] ?? false);

        try {
            $this->sendOtp($center, $remember);
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors(['otp' => 'Failed to resend OTP. Please try again.']);
        }

        return back()->with('success', 'A new OTP has been sent to your email address.');
    }

    // ── Logout ────────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        $this->guard()->logout();

        if ($centerId = session(self::OTP_SESSION_KEY)) {
            Cache::forget($this->otpCacheKey((int) $centerId));
            Cache::forget($this->otpThrottleKey((int) $centerId));
        }

        session()->forget(self::OTP_SESSION_KEY);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('center.login');
    }

    // ── Dashboard ─────────────────────────────────────────────────────
    public function dashboard()
    {
        $center = $this->guard()->user();
        $center->load('institute');

        $activeSession = AcademicSession::where('institute_id', $center->institute_id)
            ->where('is_active', true)->first();

        $totalStudents = Student::where('institute_id', $center->institute_id)
            ->where('admission_source', 'center')
            ->where('admission_source_id', $center->id)
            ->when($activeSession, fn($q) => $q->where('academic_session_id', $activeSession->id))
            ->count();

        $totalCollected = 0;
        if ($center->canCollectFee()) {
            $totalCollected = FeeInvoice::where('institute_id', $center->institute_id)
                ->where('is_cancelled', false)
                ->when($activeSession, fn($q) => $q->where('academic_session_id', $activeSession->id))
                ->whereHas('student', fn($q) => $q
                    ->where('admission_source', 'center')
                    ->where('admission_source_id', $center->id)
                )
                ->sum('paid_amount');
        }

        $recentStudents = Student::with(['stream.course', 'coursePart'])
            ->where('institute_id', $center->institute_id)
            ->where('admission_source', 'center')
            ->where('admission_source_id', $center->id)
            ->when($activeSession, fn($q) => $q->where('academic_session_id', $activeSession->id))
            ->latest()
            ->take(10)
            ->get();

        $dashboardNotices = Notice::forRole($center->institute_id, 'center')->limit(5)->get();

        $centerWallet = $center->wallet;

        return view('center.dashboard', compact(
            'center', 'activeSession', 'totalStudents', 'totalCollected', 'recentStudents',
            'dashboardNotices', 'centerWallet'
        ));
    }

    // ── Change Password ───────────────────────────────────────────────
    public function changePasswordForm()
    {
        return view('center.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $center = $this->guard()->user();

        if (!Hash::check($request->current_password, $center->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $center->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully!');
    }
}
