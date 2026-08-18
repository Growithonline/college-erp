<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Mail\StudentOtpMail;
use App\Mail\StudentRecoveryOtpMail;
use App\Models\FeeInvoice;
use App\Models\Notice;
use App\Models\NoticeRead;
use App\Models\PlatformSmsSetting;
use App\Models\Student;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Services\InstituteMailer;
use Throwable;

class StudentAuthController extends Controller
{
    private const OTP_SESSION_KEY = 'student_otp_id';

    private function guard()
    {
        return Auth::guard('student');
    }

    private function otpCacheKey(int $id): string
    {
        return "student_login_otp:{$id}";
    }

    private function otpThrottleKey(int $id): string
    {
        return "student_login_otp_throttle:{$id}";
    }

    private function pendingStudent(): ?Student
    {
        $id = session(self::OTP_SESSION_KEY);
        return $id ? Student::find($id) : null;
    }

    private function sendOtp(Student $student, bool $remember = false): void
    {
        $otp      = (string) random_int(100000, 999999);
        $platform = PlatformSmsSetting::current();
        $expiryMinutes   = $platform?->otp_expiry_minutes ?? 5;
        $cooldownSeconds = $platform?->otp_resend_cooldown_seconds ?? 30;

        if ($student->email) {
            InstituteMailer::send($student->institute_id, $student->email, new StudentOtpMail($student, $otp));
        }

        $mobile = $student->mobile ?? $student->father_mobile;
        if ($mobile) {
            try {
                SmsService::sendInstituteOtp($student->institute_id, $mobile, $otp);
            } catch (Throwable) {}
        }

        Cache::put($this->otpCacheKey($student->id), [
            'hash'     => Hash::make($otp),
            'remember' => $remember,
        ], now()->addMinutes($expiryMinutes));

        Cache::put($this->otpThrottleKey($student->id), true, now()->addSeconds($cooldownSeconds));
    }

    // ── Login Form ───────────────────────────────────────────────────
    public function loginForm()
    {
        if ($this->guard()->check()) {
            return redirect()->route('student.dashboard');
        }
        return view('student.auth.login');
    }

    // ── Login Submit ─────────────────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'student_uid' => 'required|string',
            'password'    => 'required|string',
        ]);

        $student = Student::where('student_uid', $request->student_uid)->first();

        if (!$student || !Hash::check($request->password, $student->password)) {
            return back()
                ->withInput($request->only('student_uid'))
                ->withErrors(['student_uid' => 'Invalid Student ID or password.']);
        }

        if (!$student->portal_enabled) {
            return back()->withErrors(['student_uid' => 'Your portal access has been disabled. Contact admin.']);
        }

        if ($student->isLoginBlocked()) {
            return back()->withErrors(['student_uid' => 'Your ID is blocked. Contact admin.']);
        }

        // If no email, skip OTP and login directly
        if (!$student->email) {
            $request->session()->regenerate();
            $this->guard()->login($student, $request->boolean('remember'));
            return $this->afterLogin($student);
        }

        try {
            $this->sendOtp($student, $request->boolean('remember'));
        } catch (Throwable $e) {
            report($e);
            return back()
                ->withInput($request->only('student_uid'))
                ->withErrors(['student_uid' => 'Failed to send OTP. Please try again.']);
        }

        session([self::OTP_SESSION_KEY => $student->id]);

        return redirect()->route('student.otp.form')
            ->with('success', 'OTP has been sent to your registered email/mobile.');
    }

    // ── OTP Form ─────────────────────────────────────────────────────
    public function showOtpForm()
    {
        $student = $this->pendingStudent();
        if (!$student) {
            return redirect()->route('student.login');
        }
        return view('student.auth.otp', compact('student'));
    }

    // ── OTP Verify ───────────────────────────────────────────────────
    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $student = $this->pendingStudent();
        if (!$student) {
            return redirect()->route('student.login');
        }

        $payload = Cache::get($this->otpCacheKey($student->id));
        if (!$payload) {
            return back()->withErrors(['otp' => 'OTP expired. Please login again.']);
        }

        if (!Hash::check($request->otp, $payload['hash'] ?? '')) {
            return back()->withErrors(['otp' => 'Incorrect OTP.']);
        }

        Cache::forget($this->otpCacheKey($student->id));
        Cache::forget($this->otpThrottleKey($student->id));

        $request->session()->regenerate();
        $this->guard()->login($student, (bool) ($payload['remember'] ?? false));
        session()->forget(self::OTP_SESSION_KEY);

        return $this->afterLogin($student);
    }

    // ── OTP Resend ───────────────────────────────────────────────────
    public function resendOtp()
    {
        $student = $this->pendingStudent();
        if (!$student) {
            return redirect()->route('student.login');
        }

        if (Cache::has($this->otpThrottleKey($student->id))) {
            $cooldown = PlatformSmsSetting::current()?->otp_resend_cooldown_seconds ?? 30;
            return back()->withErrors(['otp' => "Please wait {$cooldown} seconds before requesting a new OTP."]);
        }

        $remember = (bool) (Cache::get($this->otpCacheKey($student->id))['remember'] ?? false);

        try {
            $this->sendOtp($student, $remember);
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors(['otp' => 'Failed to resend OTP. Please try again.']);
        }

        return back()->with('success', 'A new OTP has been sent.');
    }

    // ── Account Recovery (Know Student ID / Forgot Password) ──────────

    private const RECOVER_SESSION_KEY  = 'student_recover_id';
    private const RECOVER_MODE_KEY     = 'student_recover_mode';
    private const RECOVER_VERIFIED_KEY = 'student_recover_verified_id';

    private function recoverOtpCacheKey(int $id): string
    {
        return "student_recover_otp:{$id}";
    }

    private function recoverOtpThrottleKey(int $id): string
    {
        return "student_recover_otp_throttle:{$id}";
    }

    private function pendingRecoverStudent(): ?Student
    {
        $id = session(self::RECOVER_SESSION_KEY);
        return $id ? Student::find($id) : null;
    }

    private function sendRecoveryOtp(Student $student): void
    {
        $otp      = (string) random_int(100000, 999999);
        $platform = PlatformSmsSetting::current();
        $expiryMinutes   = $platform?->otp_expiry_minutes ?? 5;
        $cooldownSeconds = $platform?->otp_resend_cooldown_seconds ?? 30;

        InstituteMailer::send($student->institute_id, $student->email, new StudentRecoveryOtpMail($student, $otp));

        Cache::put($this->recoverOtpCacheKey($student->id), [
            'hash' => Hash::make($otp),
        ], now()->addMinutes($expiryMinutes));

        Cache::put($this->recoverOtpThrottleKey($student->id), true, now()->addSeconds($cooldownSeconds));
    }

    public function recoverForm()
    {
        return view('student.auth.recover');
    }

    public function recoverSendOtp(Request $request)
    {
        $request->validate([
            'mode'      => 'required|in:know_id,forgot_password',
            'email'     => 'required|email',
            'aadhar_no' => 'required|digits:12',
        ]);

        $student = Student::where('email', $request->email)
            ->where('aadhar_no', $request->aadhar_no)
            ->first();

        if (!$student || !$student->email) {
            return back()
                ->withInput($request->only('mode', 'email'))
                ->withErrors(['email' => 'No student found matching this email and Aadhar number.']);
        }

        try {
            $this->sendRecoveryOtp($student);
        } catch (Throwable $e) {
            report($e);
            return back()
                ->withInput($request->only('mode', 'email'))
                ->withErrors(['email' => 'Failed to send OTP. Please try again.']);
        }

        session([
            self::RECOVER_SESSION_KEY => $student->id,
            self::RECOVER_MODE_KEY    => $request->mode,
        ]);

        return redirect()->route('student.recover.otp.form')->with('success', 'OTP has been sent to your registered email.');
    }

    public function recoverOtpForm()
    {
        $student = $this->pendingRecoverStudent();
        if (!$student) {
            return redirect()->route('student.recover');
        }
        return view('student.auth.recover-otp', compact('student'));
    }

    public function recoverVerifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $student = $this->pendingRecoverStudent();
        if (!$student) {
            return redirect()->route('student.recover');
        }

        $otpPayload = Cache::get($this->recoverOtpCacheKey($student->id));
        if (!$otpPayload) {
            return back()->withErrors(['otp' => 'OTP expired. Please start again.']);
        }

        if (!Hash::check($request->otp, $otpPayload['hash'] ?? '')) {
            return back()->withErrors(['otp' => 'Incorrect OTP.']);
        }

        Cache::forget($this->recoverOtpCacheKey($student->id));
        Cache::forget($this->recoverOtpThrottleKey($student->id));

        $mode = session(self::RECOVER_MODE_KEY);
        session()->forget([self::RECOVER_SESSION_KEY, self::RECOVER_MODE_KEY]);

        if ($mode === 'know_id') {
            return redirect()->route('student.recover.result')->with('recovered_student_uid', $student->student_uid);
        }

        session([self::RECOVER_VERIFIED_KEY => $student->id]);
        return redirect()->route('student.recover.reset-password.form');
    }

    public function recoverResendOtp()
    {
        $student = $this->pendingRecoverStudent();
        if (!$student) {
            return redirect()->route('student.recover');
        }

        if (Cache::has($this->recoverOtpThrottleKey($student->id))) {
            $cooldown = PlatformSmsSetting::current()?->otp_resend_cooldown_seconds ?? 30;
            return back()->withErrors(['otp' => "Please wait {$cooldown} seconds before requesting a new OTP."]);
        }

        try {
            $this->sendRecoveryOtp($student);
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors(['otp' => 'Failed to resend OTP. Please try again.']);
        }

        return back()->with('success', 'A new OTP has been sent.');
    }

    public function recoverResultId()
    {
        if (!session('recovered_student_uid')) {
            return redirect()->route('student.recover');
        }
        return view('student.auth.recover-result');
    }

    public function recoverResetPasswordForm()
    {
        if (!session(self::RECOVER_VERIFIED_KEY)) {
            return redirect()->route('student.recover');
        }
        return view('student.auth.recover-reset-password');
    }

    public function recoverResetPassword(Request $request)
    {
        $studentId = session(self::RECOVER_VERIFIED_KEY);
        if (!$studentId) {
            return redirect()->route('student.recover');
        }

        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $student = Student::find($studentId);
        if (!$student) {
            session()->forget(self::RECOVER_VERIFIED_KEY);
            return redirect()->route('student.recover');
        }

        $student->update([
            'password'    => Hash::make($request->password),
            'first_login' => false,
        ]);

        session()->forget(self::RECOVER_VERIFIED_KEY);

        return redirect()->route('student.login')->with('success', 'Password reset successfully! Please login with your new password.');
    }

    // ── After Login ──────────────────────────────────────────────────
    private function afterLogin(Student $student)
    {
        if ($student->first_login) {
            return redirect()->route('student.change-password')
                ->with('info', 'Please change your temporary password to continue.');
        }
        return redirect()->intended(route('student.dashboard'));
    }

    // ── Logout ───────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        $studentId = $this->guard()->id();
        $this->guard()->logout();

        if ($studentId) {
            Cache::forget($this->otpCacheKey($studentId));
            Cache::forget($this->otpThrottleKey($studentId));
        }

        session()->forget(self::OTP_SESSION_KEY);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login');
    }

    // ── Dashboard ────────────────────────────────────────────────────
    public function dashboard()
    {
        $student = $this->guard()->user();
        $student->load([
            'institute',
            'session',
            'stream.course',
            'coursePart',
            'educationDetails',
            'subjects' => fn($q) => $q->wherePivot('academic_session_id', $student->academic_session_id),
        ]);

        // Fee invoices for current session
        $invoices = FeeInvoice::with('items')
            ->where('student_id', $student->id)
            ->where('academic_session_id', $student->academic_session_id)
            ->where('is_cancelled', false)
            ->orderByDesc('payment_date')
            ->get();

        $totalFee  = $invoices->sum('total_amount');
        $totalPaid = $invoices->sum('paid_amount');
        $totalDue  = $totalFee - $totalPaid;

        // Notices for students
        $notices = Notice::forRole($student->institute_id, 'students')
            ->limit(20)
            ->get();

        $readNoticeIds = NoticeRead::where('reader_type', 'student')
            ->where('reader_id', $student->id)
            ->pluck('notice_id')
            ->toArray();

        // Transport
        $transport = $student->activeTransportAllocation()
            ->with(['route', 'stop', 'vehicle', 'driver'])
            ->first();

        return view('student.dashboard', compact(
            'student',
            'invoices', 'totalFee', 'totalPaid', 'totalDue',
            'notices', 'readNoticeIds',
            'transport'
        ));
    }

    // ── Mark Notice as Read ──────────────────────────────────────────
    public function markNoticeRead(Request $request, int $noticeId)
    {
        $student = $this->guard()->user();

        NoticeRead::firstOrCreate([
            'notice_id'   => $noticeId,
            'reader_type' => 'student',
            'reader_id'   => $student->id,
        ], ['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    // ── Change Password Form ─────────────────────────────────────────
    public function changePasswordForm()
    {
        return view('student.change-password');
    }

    // ── Change Password Submit ───────────────────────────────────────
    public function changePassword(Request $request)
    {
        $student = $this->guard()->user();

        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $student->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $student->update([
            'password'    => Hash::make($request->password),
            'first_login' => false,
        ]);

        return redirect()->route('student.dashboard')
            ->with('success', 'Password changed successfully! Welcome to your dashboard.');
    }
}
