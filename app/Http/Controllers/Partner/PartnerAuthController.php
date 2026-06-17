<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Mail\PartnerOtpMail;
use App\Models\AcademicSession;
use App\Models\ChannelPartner;
use App\Models\FeeInvoice;
use App\Models\Notice;
use App\Models\PlatformSmsSetting;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Services\InstituteMailer;
use Throwable;

class PartnerAuthController extends Controller
{
    private const OTP_SESSION_KEY = 'partner_otp_user_id';

    private function guard()
    {
        return Auth::guard('partner');
    }

    private function otpCacheKey(int $partnerId): string
    {
        return "partner_login_otp:{$partnerId}";
    }

    private function otpThrottleKey(int $partnerId): string
    {
        return "partner_login_otp_throttle:{$partnerId}";
    }

    private function pendingPartner(): ?ChannelPartner
    {
        $partnerId = session(self::OTP_SESSION_KEY);
        return $partnerId ? ChannelPartner::find($partnerId) : null;
    }

    private function sendOtp(ChannelPartner $partner, bool $remember = false): void
    {
        $otp      = (string) random_int(100000, 999999);
        $platform = PlatformSmsSetting::current();
        $expiryMinutes   = $platform?->otp_expiry_minutes ?? 5;
        $cooldownSeconds = $platform?->otp_resend_cooldown_seconds ?? 30;

        InstituteMailer::send($partner->institute_id, $partner->email, new PartnerOtpMail($partner, $otp));

        Cache::put($this->otpCacheKey($partner->id), [
            'hash'     => Hash::make($otp),
            'remember' => $remember,
        ], now()->addMinutes($expiryMinutes));

        Cache::put($this->otpThrottleKey($partner->id), true, now()->addSeconds($cooldownSeconds));
    }

    // ── Login Form ────────────────────────────────────────────────────
    public function loginForm()
    {
        if ($this->guard()->check()) {
            return redirect()->route('partner.dashboard');
        }
        return view('partner.auth.login');
    }

    // ── Login Submit ──────────────────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $partner = ChannelPartner::where('email', $request->email)->first();

        if (!$partner || !Hash::check($request->password, $partner->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        if (!$partner->status) {
            return back()->withErrors(['email' => 'Your account has been disabled.']);
        }

        try {
            $this->sendOtp($partner, $request->boolean('remember'));
        } catch (Throwable $e) {
            report($e);
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Failed to send OTP email. Please try again.']);
        }

        session([self::OTP_SESSION_KEY => $partner->id]);

        return redirect()->route('partner.otp.form')->with('success', 'OTP has been sent to your email address.');
    }

    // ── OTP Form ──────────────────────────────────────────────────────
    public function showOtpForm()
    {
        $partner = $this->pendingPartner();

        if (!$partner) {
            return redirect()->route('partner.login');
        }

        return view('partner.auth.otp', compact('partner'));
    }

    // ── OTP Verify ────────────────────────────────────────────────────
    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $partner = $this->pendingPartner();

        if (!$partner) {
            return redirect()->route('partner.login');
        }

        $otpPayload = Cache::get($this->otpCacheKey($partner->id));

        if (!$otpPayload) {
            return back()->withErrors(['otp' => 'OTP expired. Please login again.']);
        }

        if (!Hash::check($request->otp, $otpPayload['hash'] ?? '')) {
            return back()->withErrors(['otp' => 'Incorrect OTP.']);
        }

        Cache::forget($this->otpCacheKey($partner->id));
        Cache::forget($this->otpThrottleKey($partner->id));

        $request->session()->regenerate();
        $this->guard()->login($partner, (bool) ($otpPayload['remember'] ?? false));
        session()->forget(self::OTP_SESSION_KEY);

        return redirect()->intended(route('partner.dashboard'));
    }

    // ── OTP Resend ────────────────────────────────────────────────────
    public function resendOtp()
    {
        $partner = $this->pendingPartner();

        if (!$partner) {
            return redirect()->route('partner.login');
        }

        if (Cache::has($this->otpThrottleKey($partner->id))) {
            $cooldown = PlatformSmsSetting::current()?->otp_resend_cooldown_seconds ?? 30;
            return back()->withErrors(['otp' => "Please wait {$cooldown} seconds before requesting a new OTP."]);
        }

        $remember = (bool) (Cache::get($this->otpCacheKey($partner->id))['remember'] ?? false);

        try {
            $this->sendOtp($partner, $remember);
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

        if ($partnerId = session(self::OTP_SESSION_KEY)) {
            Cache::forget($this->otpCacheKey((int) $partnerId));
            Cache::forget($this->otpThrottleKey((int) $partnerId));
        }

        session()->forget(self::OTP_SESSION_KEY);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('partner.login');
    }

    // ── Dashboard ─────────────────────────────────────────────────────
    public function dashboard()
    {
        $partner = $this->guard()->user();
        $partner->load('institute');

        $activeSession = AcademicSession::where('institute_id', $partner->institute_id)
            ->where('is_active', true)->first();

        $totalStudents = Student::where('institute_id', $partner->institute_id)
            ->where('admission_source', 'channel_partner')
            ->where('admission_source_id', $partner->id)
            ->when($activeSession, fn($q) => $q->where('academic_session_id', $activeSession->id))
            ->count();

        $totalCollected = 0;
        if ($partner->canCollectFee()) {
            $totalCollected = FeeInvoice::where('institute_id', $partner->institute_id)
                ->where('is_cancelled', false)
                ->when($activeSession, fn($q) => $q->where('academic_session_id', $activeSession->id))
                ->whereHas('student', fn($q) => $q
                    ->where('admission_source', 'channel_partner')
                    ->where('admission_source_id', $partner->id)
                )
                ->sum('paid_amount');
        }

        $totalCommission = $partner->commission_percent > 0
            ? $totalCollected * $partner->commission_percent / 100
            : 0;

        $recentStudents = Student::with(['stream.course'])
            ->where('institute_id', $partner->institute_id)
            ->where('admission_source', 'channel_partner')
            ->where('admission_source_id', $partner->id)
            ->when($activeSession, fn($q) => $q->where('academic_session_id', $activeSession->id))
            ->latest()
            ->take(10)
            ->get();

        $dashboardNotices = Notice::forRole($partner->institute_id, 'channel')->limit(5)->get();

        $channelWallet = $partner->wallet;

        return view('partner.dashboard', compact(
            'partner', 'activeSession',
            'totalStudents', 'totalCollected', 'totalCommission',
            'recentStudents', 'dashboardNotices', 'channelWallet'
        ));
    }

    // ── Change Password ───────────────────────────────────────────────
    public function changePasswordForm()
    {
        return view('partner.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $partner = $this->guard()->user();

        if (!Hash::check($request->input('current_password'), $partner->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $partner->update(['password' => Hash::make($request->input('password'))]);

        return back()->with('success', 'Password changed successfully!');
    }
}
