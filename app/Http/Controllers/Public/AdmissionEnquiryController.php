<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\EnquiryOtpMail;
use App\Models\Course;
use App\Models\Enquiry;
use App\Models\EnquiryEmailOtp;
use App\Models\Institute;
use App\Services\InstituteMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AdmissionEnquiryController extends Controller
{
    private function resolveInstitute(string $shortName): Institute
    {
        $institute = Institute::where('short_name', strtoupper($shortName))->first();

        abort_if(!$institute || $institute->status !== 'active', 404);

        return $institute;
    }

    private function otpThrottleKey(string $email, Request $request): string
    {
        return 'enquiry-otp|' . strtolower($email) . '|' . $request->ip();
    }

    private function sessionVerifiedKey(int $instituteId): string
    {
        return 'enquiry_verified_email_' . $instituteId;
    }

    public function show(Request $request, string $shortName)
    {
        $institute = $this->resolveInstitute($shortName);

        $courses = Course::where('institute_id', $institute->id)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        return view('public.admission.enquiry', [
            'institute' => $institute,
            'courses'   => $courses,
            'utm'       => $request->only(['utm_source', 'utm_medium', 'utm_campaign']),
        ]);
    }

    public function sendOtp(Request $request, string $shortName)
    {
        $institute = $this->resolveInstitute($shortName);

        $request->validate(['email' => 'required|email|max:255']);
        $email = strtolower((string) $request->input('email'));

        $throttleKey = $this->otpThrottleKey($email, $request);
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            throw ValidationException::withMessages([
                'email' => 'Too many OTP requests. Please try again after some time.',
            ]);
        }
        RateLimiter::hit($throttleKey, 900);

        $otp = EnquiryEmailOtp::generateFor($email);
        InstituteMailer::send($institute->id, $email, new EnquiryOtpMail($institute, $otp));

        return response()->json(['message' => 'OTP sent to your email.']);
    }

    public function verifyOtp(Request $request, string $shortName)
    {
        $institute = $this->resolveInstitute($shortName);

        $request->validate([
            'email' => 'required|email|max:255',
            'otp'   => 'required|string|size:6',
        ]);
        $email = strtolower((string) $request->input('email'));

        if (!EnquiryEmailOtp::attemptVerify($email, (string) $request->input('otp'))) {
            throw ValidationException::withMessages(['otp' => 'Invalid or expired OTP.']);
        }

        $request->session()->put($this->sessionVerifiedKey($institute->id), $email);

        return response()->json(['message' => 'Email verified.']);
    }

    public function store(Request $request, string $shortName)
    {
        $institute = $this->resolveInstitute($shortName);

        if (filled($request->input('website'))) {
            // Honeypot field — bots fill hidden fields, humans never see them.
            abort(422);
        }

        $verifiedEmail = $request->session()->get($this->sessionVerifiedKey($institute->id));

        $validated = $request->validate([
            'name'        => 'required|string|max:150',
            'mobile'      => 'required|string|max:20',
            'email'       => 'required|email|max:255',
            'course_id'   => 'nullable|integer|exists:courses,id',
            'city'        => 'nullable|string|max:100',
            'utm_source'   => 'nullable|string|max:100',
            'utm_medium'   => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
        ]);

        $email = strtolower($validated['email']);
        if (!$verifiedEmail || $verifiedEmail !== $email) {
            throw ValidationException::withMessages(['email' => 'Please verify your email with OTP first.']);
        }

        if (!empty($validated['course_id'])) {
            $belongsToInstitute = Course::where('id', $validated['course_id'])
                ->where('institute_id', $institute->id)
                ->exists();
            abort_unless($belongsToInstitute, 422);
        }

        Enquiry::create([
            'institute_id'      => $institute->id,
            'name'              => $validated['name'],
            'mobile'            => $validated['mobile'],
            'email'             => $email,
            'course_id'         => $validated['course_id'] ?? null,
            'city'              => $validated['city'] ?? null,
            'source'            => 'website',
            'utm_source'        => $validated['utm_source'] ?? null,
            'utm_medium'        => $validated['utm_medium'] ?? null,
            'utm_campaign'      => $validated['utm_campaign'] ?? null,
            'email_verified_at' => now(),
        ]);

        $request->session()->forget($this->sessionVerifiedKey($institute->id));

        return view('public.admission.thank-you', ['institute' => $institute]);
    }
}
