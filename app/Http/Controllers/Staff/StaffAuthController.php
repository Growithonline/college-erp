<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Notice;
use App\Models\Student;
use App\Models\FeeInvoice;
use App\Models\PlatformSmsSetting;
use App\Models\StaffMember;
use App\Mail\StaffOtpMail;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\InstituteMailer;
use Throwable;

class StaffAuthController extends Controller
{
    private const OTP_SESSION_KEY = 'staff_otp_user_id';

    private function guard()
    {
        return Auth::guard('staff');
    }

    private function otpCacheKey(int $staffId): string
    {
        return "staff_login_otp:{$staffId}";
    }

    private function otpThrottleKey(int $staffId): string
    {
        return "staff_login_otp_throttle:{$staffId}";
    }

    private function pendingStaff(): ?StaffMember
    {
        $staffId = session(self::OTP_SESSION_KEY);

        if (!$staffId) {
            return null;
        }

        return StaffMember::find($staffId);
    }

    private function sendOtp(StaffMember $staff, bool $remember = false): void
    {
        $otp      = (string) random_int(100000, 999999);
        $platform = PlatformSmsSetting::current();
        $expiryMinutes   = $platform?->otp_expiry_minutes ?? 5;
        $cooldownSeconds = $platform?->otp_resend_cooldown_seconds ?? 30;

        InstituteMailer::send($staff->institute_id, $staff->email, new StaffOtpMail($staff, $otp));

        if ($staff->mobile) {
            SmsService::sendOtp($staff->mobile, $otp);
        }

        Cache::put($this->otpCacheKey($staff->id), [
            'hash'     => Hash::make($otp),
            'remember' => $remember,
            'sms_sent' => (bool) $staff->mobile,
        ], now()->addMinutes($expiryMinutes));

        Cache::put($this->otpThrottleKey($staff->id), true, now()->addSeconds($cooldownSeconds));
    }

    public function loginForm()
    {
        if ($this->guard()->check()) {
            return redirect()->route('staff.dashboard');
        }
        return view('staff.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $staff = StaffMember::with('role', 'institute')->where('email', $request->email)->first();

        if (!$staff || !Hash::check($request->password, $staff->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        if (!$staff->status) {
            return back()->withErrors(['email' => 'Your account has been disabled.']);
        }

        try {
            $this->sendOtp($staff, $request->boolean('remember'));
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Failed to send OTP email. Please try again.']);
        }

        session([self::OTP_SESSION_KEY => $staff->id]);

        return redirect()->route('staff.otp.form')->with('success', 'OTP has been sent to your email address.');
    }

    public function showOtpForm()
    {
        $staff = $this->pendingStaff();

        if (!$staff) {
            return redirect()->route('staff.login');
        }

        return view('staff.auth.otp', compact('staff'));
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $staff = $this->pendingStaff();

        if (!$staff) {
            return redirect()->route('staff.login');
        }

        $otpPayload = Cache::get($this->otpCacheKey($staff->id));

        if (!$otpPayload) {
            return back()->withErrors(['otp' => 'OTP expired. Please login again.']);
        }

        if (!Hash::check($request->otp, $otpPayload['hash'] ?? '')) {
            return back()->withErrors(['otp' => 'Incorrect OTP.']);
        }

        Cache::forget($this->otpCacheKey($staff->id));
        Cache::forget($this->otpThrottleKey($staff->id));

        $request->session()->regenerate();
        $this->guard()->login($staff, (bool) ($otpPayload['remember'] ?? false));
        session()->forget(self::OTP_SESSION_KEY);

        return redirect()->intended(route('staff.dashboard'));
    }

    public function resendOtp()
    {
        $staff = $this->pendingStaff();

        if (!$staff) {
            return redirect()->route('staff.login');
        }

        if (Cache::has($this->otpThrottleKey($staff->id))) {
            $cooldown = PlatformSmsSetting::current()?->otp_resend_cooldown_seconds ?? 30;
            return back()->withErrors(['otp' => "Please wait {$cooldown} seconds before requesting a new OTP."]);
        }

        $remember = (bool) (Cache::get($this->otpCacheKey($staff->id))['remember'] ?? false);

        try {
            $this->sendOtp($staff, $remember);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['otp' => 'Failed to resend OTP. Please try again.']);
        }

        return back()->with('success', 'A new OTP has been sent to your email address.');
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();
        if ($staffId = session(self::OTP_SESSION_KEY)) {
            Cache::forget($this->otpCacheKey((int) $staffId));
            Cache::forget($this->otpThrottleKey((int) $staffId));
        }
        session()->forget(self::OTP_SESSION_KEY);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('staff.login');
    }

    public function dashboard()
    {
        $staff = $this->guard()->user();
        $staff->load('institute', 'role');

        $activeSession = AcademicSession::where('institute_id', $staff->institute_id)
            ->where('is_active', true)->first();

        // Stats based on permissions
        $todayCollected = 0;
        $todayAdmissions = 0;
        $totalStudents = 0;

        if ($staff->canViewFeeHistory()) {
            $feeQuery = FeeInvoice::where('institute_id', $staff->institute_id)
                ->where('is_cancelled', false)
                ->whereDate('payment_date', today());
            $staff->scopeFeeInvoices($feeQuery);
            $todayCollected = $feeQuery->sum('paid_amount');
        }

        if ($staff->canViewAdmissions()) {
            $todayAdmissionsQuery = Student::where('institute_id', $staff->institute_id)
                ->whereDate('admission_date', today());
            $staff->scopeAdmissionStudents($todayAdmissionsQuery);
            $todayAdmissions = $todayAdmissionsQuery->count();

            $totalStudentsQuery = Student::where('institute_id', $staff->institute_id)
                ->when($activeSession, fn($q) => $q->where('academic_session_id', $activeSession->id));
            $staff->scopeAdmissionStudents($totalStudentsQuery);
            $totalStudents = $totalStudentsQuery->count();
        }

        // Recent fee collections
        $recentCollections = collect();
        if ($staff->canViewFeeHistory()) {
            $recentCollectionsQuery = FeeInvoice::with('student')
                ->where('institute_id', $staff->institute_id)
                ->where('is_cancelled', false);
            $staff->scopeFeeInvoices($recentCollectionsQuery);
            $recentCollections = $recentCollectionsQuery->latest('payment_date')
                ->take(8)
                ->get();
        }

        $dashboardNotices = Notice::forRole($staff->institute_id, 'staff')->limit(5)->get();

        return view('staff.dashboard', compact(
            'staff', 'activeSession',
            'todayCollected', 'todayAdmissions', 'totalStudents',
            'recentCollections', 'dashboardNotices'
        ));
    }

    public function profile()
    {
        $staff = $this->guard()->user();
        $staff->load('institute', 'role');
        return view('staff.profile', compact('staff'));
    }

    public function changePasswordForm()
    {
        return view('staff.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $staff = $this->guard()->user();

        if (!Hash::check($request->current_password, $staff->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $staff->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Password changed successfully!');
    }
}
