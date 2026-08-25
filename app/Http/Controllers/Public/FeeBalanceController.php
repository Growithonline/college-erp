<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CourseType;
use App\Models\Institute;
use App\Models\PlatformSmsSetting;
use App\Models\Student;
use App\Services\SmsService;
use App\Support\AcademicState;
use App\Traits\BuildsStudentStatements;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class FeeBalanceController extends Controller
{
    use BuildsStudentStatements;

    private const IDENTIFIER_COLUMNS = [
        'student_uid'   => 'Student ID',
        'aadhar_no'     => 'Aadhar No',
        'enrollment_no' => 'Enrollment No',
        'uin_no'        => 'UIN',
        'roll_no'       => 'Roll No',
    ];

    private const GENERIC_MISMATCH_MESSAGE = 'We could not verify these details. Please check and try again.';
    private const GENERIC_OTP_MESSAGE      = 'Incorrect or expired OTP. Please start again.';
    private const OTP_ATTEMPT_CAP          = 5;
    private const RESEND_CAP               = 3;

    private function resolveInstitute(string $shortName): Institute
    {
        $institute = Institute::where('short_name', strtoupper($shortName))->first();

        abort_if(!$institute || $institute->status !== 'active' || !$institute->fee_balance_enabled, 404);

        return $institute;
    }

    // ── Captcha (session-based, single-use) ────────────────────────────────

    private function captchaSessionKey(int $instituteId): string
    {
        return "fee_balance_captcha_{$instituteId}";
    }

    private function issueCaptcha(Request $request, int $instituteId): string
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);

        $request->session()->put($this->captchaSessionKey($instituteId), [
            'a'      => $a,
            'b'      => $b,
            'answer' => $a + $b,
        ]);

        return "{$a} + {$b} = ?";
    }

    /**
     * Checks the submitted answer, then always rotates to a fresh challenge
     * (pass or fail) so a solved captcha can never be replayed across attempts.
     * Returns [valid, newQuestion].
     */
    private function validateAndRotateCaptcha(Request $request, int $instituteId, $submitted): array
    {
        $stored = $request->session()->get($this->captchaSessionKey($instituteId));
        $valid  = $stored && (int) $submitted === (int) $stored['answer'];

        $newQuestion = $this->issueCaptcha($request, $instituteId);

        return [(bool) $valid, $newQuestion];
    }

    // ── OTP (cache-based, opaque token) ─────────────────────────────────────

    private function otpCacheKey(string $token): string
    {
        return "fee_balance_otp:{$token}";
    }

    private function otpThrottleKey(string $token): string
    {
        return "fee_balance_otp_throttle:{$token}";
    }

    // ── Rate limiting ────────────────────────────────────────────────────────

    private function matchThrottleKeys(Request $request, int $instituteId, string $identifierType, string $identifierValue): array
    {
        return [
            "fee-balance-verify|{$instituteId}|{$request->ip()}",
            "fee-balance-verify|{$instituteId}|{$identifierType}|" . hash('sha256', $identifierValue),
        ];
    }

    // ── Actions ──────────────────────────────────────────────────────────────

    public function show(Request $request, string $shortName)
    {
        $institute = $this->resolveInstitute($shortName);

        $courseTypes = CourseType::forInstitute($institute->id)
            ->active()
            ->orderBy('sort_order')
            ->with(['courses' => fn ($q) => $q->where('status', true)->orderBy('name')->with('streams')])
            ->get();

        $captchaQuestion = $this->issueCaptcha($request, $institute->id);

        return view('public.fee-balance.show', [
            'institute'         => $institute,
            'courseTypes'       => $courseTypes,
            'identifierOptions' => self::IDENTIFIER_COLUMNS,
            'captchaQuestion'   => $captchaQuestion,
        ]);
    }

    public function captcha(Request $request, string $shortName)
    {
        $institute = $this->resolveInstitute($shortName);

        return response()->json(['question' => $this->issueCaptcha($request, $institute->id)]);
    }

    public function verify(Request $request, string $shortName)
    {
        $institute = $this->resolveInstitute($shortName);

        if (filled($request->input('website'))) {
            // Honeypot field — bots fill hidden fields, humans never see them.
            abort(422);
        }

        $validated = $request->validate([
            'course_type_id'   => ['required', 'integer', Rule::exists('course_types', 'id')->where('institute_id', $institute->id)],
            'course_id'        => ['required', 'integer', Rule::exists('courses', 'id')->where('institute_id', $institute->id)],
            'course_stream_id' => ['required', 'integer', Rule::exists('course_streams', 'id')->where('course_id', $request->input('course_id'))],
            'semester'         => ['nullable', 'integer', 'min:0', 'max:20'],
            'identifier_type'  => ['required', Rule::in(array_keys(self::IDENTIFIER_COLUMNS))],
            'identifier_value' => ['required', 'string', 'max:100'],
            'dob'              => ['required', 'date'],
            'mobile'           => ['required', 'string', 'max:20'],
            'captcha_answer'   => ['required', 'integer'],
            'website'          => ['nullable'],
        ]);

        [$captchaValid, $newQuestion] = $this->validateAndRotateCaptcha($request, $institute->id, $validated['captcha_answer']);
        if (!$captchaValid) {
            return response()->json([
                'message'  => 'Incorrect answer to the verification question.',
                'field'    => 'captcha',
                'question' => $newQuestion,
            ], 422);
        }

        // Safe: identifier_type is validated above against Rule::in(array_keys(IDENTIFIER_COLUMNS)),
        // so this is always one of the fixed column names below — never raw user input used as a column name.
        $identifierColumn = $validated['identifier_type'];
        $identifierValue  = trim($validated['identifier_value']);
        if ($identifierColumn === 'aadhar_no') {
            // Stored as 12 digits only (see AdmissionApplicationController's `regex:/^\d{12}$/`
            // rule) — strip spaces/dashes so "1234 5678 9012" still matches.
            $identifierValue = preg_replace('/\D/', '', $identifierValue);
        }

        [$ipInstituteKey, $identifierKey] = $this->matchThrottleKeys($request, $institute->id, $validated['identifier_type'], $identifierValue);

        if (RateLimiter::tooManyAttempts($ipInstituteKey, 10) || RateLimiter::tooManyAttempts($identifierKey, 5)) {
            return response()->json(['message' => 'Too many attempts. Please try again later.'], 429);
        }

        $mobileNormalized = substr(preg_replace('/\D/', '', $validated['mobile']), -10);

        $candidates = Student::query()
            ->where('institute_id', $institute->id)
            ->where('status', '!=', 'cancelled')
            ->where('course_stream_id', $validated['course_stream_id'])
            ->when(!empty($validated['semester']), fn ($q) => $q->where('current_semester', $validated['semester']))
            ->where($identifierColumn, $identifierValue)
            ->whereDate('dob', $validated['dob'])
            ->whereNotNull('mobile')
            ->get();

        RateLimiter::hit($ipInstituteKey, 1800);
        RateLimiter::hit($identifierKey, 1800);

        $matches = $candidates->filter(function (Student $student) use ($mobileNormalized) {
            return substr(preg_replace('/\D/', '', (string) $student->mobile), -10) === $mobileNormalized;
        });

        if ($matches->count() !== 1) {
            return response()->json(['message' => self::GENERIC_MISMATCH_MESSAGE], 422);
        }

        $student = $matches->first();

        if ($institute->fee_balance_otp_bypass) {
            return response()->json(array_merge(
                ['skip_otp' => true],
                $this->resultFor($student, $institute->id)
            ));
        }

        $otp   = (string) random_int(100000, 999999);
        $token = Str::random(48);

        $platform        = PlatformSmsSetting::current();
        $expiryMinutes   = $platform?->otp_expiry_minutes ?? 5;
        $cooldownSeconds = $platform?->otp_resend_cooldown_seconds ?? 30;

        try {
            $sent = SmsService::sendInstituteOtp($institute->id, $student->mobile, $otp);
        } catch (Throwable $e) {
            report($e);
            $sent = false;
        }

        if (!$sent) {
            return response()->json(['message' => 'Failed to send OTP. Please try again later.'], 500);
        }

        Cache::put($this->otpCacheKey($token), [
            'institute_id' => $institute->id,
            'student_id'   => $student->id,
            'hash'         => Hash::make($otp),
            'attempts'     => 0,
            'resends'      => 0,
        ], now()->addMinutes($expiryMinutes));

        Cache::put($this->otpThrottleKey($token), true, now()->addSeconds($cooldownSeconds));

        return response()->json([
            'token'   => $token,
            'message' => 'An OTP has been sent to the mobile number on file.',
        ]);
    }

    public function verifyOtp(Request $request, string $shortName)
    {
        $institute = $this->resolveInstitute($shortName);

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'otp'   => ['required', 'digits:6'],
        ]);

        $payload = Cache::get($this->otpCacheKey($validated['token']));

        if (!$payload || (int) $payload['institute_id'] !== $institute->id) {
            return response()->json(['message' => self::GENERIC_OTP_MESSAGE], 422);
        }

        if ($payload['attempts'] >= self::OTP_ATTEMPT_CAP) {
            Cache::forget($this->otpCacheKey($validated['token']));
            return response()->json(['message' => self::GENERIC_OTP_MESSAGE], 422);
        }

        if (!Hash::check($validated['otp'], $payload['hash'])) {
            $payload['attempts']++;
            Cache::put($this->otpCacheKey($validated['token']), $payload, now()->addMinutes(
                PlatformSmsSetting::current()?->otp_expiry_minutes ?? 5
            ));
            return response()->json(['message' => self::GENERIC_OTP_MESSAGE], 422);
        }

        $student = Student::where('institute_id', $institute->id)->find($payload['student_id']);

        Cache::forget($this->otpCacheKey($validated['token']));
        Cache::forget($this->otpThrottleKey($validated['token']));

        if (!$student) {
            return response()->json(['message' => self::GENERIC_OTP_MESSAGE], 422);
        }

        return response()->json($this->resultFor($student, $institute->id));
    }

    /**
     * The result payload shown after verification: due amount plus the basic
     * identity fields the institute requires on screen (name, father's name,
     * roll no, course, year/semester, session) — not the mobile number or
     * anything beyond what the student already supplied to search.
     */
    private function resultFor(Student $student, int $instituteId): array
    {
        $balances = $this->buildBalances($student, $instituteId);
        $due      = (float) ($balances->last()['due'] ?? 0);

        $course = $student->stream?->course;

        $yearLabel = AcademicState::yearLabel(
            $course?->structure_type,
            $student->current_semester,
            $student->coursePart?->year_number,
            $course?->effectiveSemestersPerYear() ?? 0
        );

        return [
            'due'         => number_format($due, 2),
            'name'        => $student->name,
            'father_name' => $student->father_name,
            'roll_no'     => $student->roll_no,
            'course'      => $course?->name,
            'year'        => $yearLabel,
            'semester'    => $student->current_semester,
            'session'     => $student->session?->name,
        ];
    }

    public function resendOtp(Request $request, string $shortName)
    {
        $institute = $this->resolveInstitute($shortName);

        $validated = $request->validate(['token' => ['required', 'string']]);

        $payload = Cache::get($this->otpCacheKey($validated['token']));

        if (!$payload || (int) $payload['institute_id'] !== $institute->id) {
            return response()->json(['message' => self::GENERIC_OTP_MESSAGE], 422);
        }

        if (Cache::has($this->otpThrottleKey($validated['token']))) {
            return response()->json(['message' => 'Please wait before requesting a new OTP.'], 429);
        }

        if ($payload['resends'] >= self::RESEND_CAP) {
            Cache::forget($this->otpCacheKey($validated['token']));
            return response()->json(['message' => 'Resend limit reached. Please start again.'], 422);
        }

        $student = Student::where('institute_id', $institute->id)->find($payload['student_id']);
        if (!$student) {
            Cache::forget($this->otpCacheKey($validated['token']));
            return response()->json(['message' => self::GENERIC_OTP_MESSAGE], 422);
        }

        $otp = (string) random_int(100000, 999999);

        $platform        = PlatformSmsSetting::current();
        $expiryMinutes   = $platform?->otp_expiry_minutes ?? 5;
        $cooldownSeconds = $platform?->otp_resend_cooldown_seconds ?? 30;

        try {
            $sent = SmsService::sendInstituteOtp($institute->id, $student->mobile, $otp);
        } catch (Throwable $e) {
            report($e);
            $sent = false;
        }

        if (!$sent) {
            return response()->json(['message' => 'Failed to send OTP. Please try again later.'], 500);
        }

        $payload['hash']    = Hash::make($otp);
        $payload['resends'] = $payload['resends'] + 1;

        Cache::put($this->otpCacheKey($validated['token']), $payload, now()->addMinutes($expiryMinutes));
        Cache::put($this->otpThrottleKey($validated['token']), true, now()->addSeconds($cooldownSeconds));

        return response()->json(['message' => 'A new OTP has been sent.']);
    }
}
