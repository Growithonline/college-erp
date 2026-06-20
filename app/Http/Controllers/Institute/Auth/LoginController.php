<?php

namespace App\Http\Controllers\Institute\Auth;

use App\Http\Controllers\Controller;
use App\Mail\InstituteOtpMail;
use App\Models\Institute;
use App\Models\PlatformSmsSetting;
use App\Models\User;
use App\Models\LoginOtp;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('institute.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'institute_id' => 'required',
            'email'        => 'required|email',
            'password'     => 'required',
        ]);

        $institute = Institute::where('institute_uid', $request->institute_id)->first();

        if (!$institute) {
            return back()->withErrors(['institute_id' => 'Invalid Institute ID']);
        }

        $user = User::where('institute_id', $institute->id)
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Invalid credentials']);
        }

        $otp      = (string) random_int(100000, 999999);
        $platform = PlatformSmsSetting::current();
        $expiryMinutes = $platform?->otp_expiry_minutes ?? 5;

        LoginOtp::where('user_id', $user->id)
            ->where('is_used', false)
            ->delete();

        LoginOtp::create([
            'user_id'   => $user->id,
            'otp'       => Hash::make($otp),
            'expires_at'=> now()->addMinutes($expiryMinutes),
            'is_used'   => false,
        ]);

        Mail::to($user->email)->send(new InstituteOtpMail($user, $otp));

        $smsSent = false;
        if ($user->mobile) {
            $smsSent = SmsService::sendOtp($user->mobile, $otp);
        }

        session(['otp_user_id' => $user->id, 'otp_sms_sent' => $smsSent]);

        return redirect()->route('otp.form');
    }

    public function showOtpForm()
    {
        if (!session()->has('otp_user_id')) {
            return redirect()->route('login');
        }

        $platform        = PlatformSmsSetting::current();
        $cooldownSeconds = $platform?->otp_resend_cooldown_seconds ?? 30;

        return view('institute.auth.otp', compact('cooldownSeconds'));
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6'
        ]);

        $userId = session('otp_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $otpRecord = LoginOtp::where('user_id', $userId)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (!$otpRecord) {
            return back()->withErrors(['otp' => 'Invalid OTP']);
        }

        if ($otpRecord->expires_at < now()) {
            return back()->withErrors(['otp' => 'OTP expired']);
        }

        // Test bypass is ONLY allowed in local/testing environments — never in production
        $isTestBypass = !app()->isProduction()
            && config('app.playwright_testing')
            && $request->otp === '999999';

        if (!$isTestBypass && !Hash::check($request->otp, $otpRecord->otp)) {
            return back()->withErrors(['otp' => 'Incorrect OTP']);
        }

        $otpRecord->update(['is_used' => true]);

        $user = User::find($userId);

        Auth::login($user);

        session()->forget('otp_user_id');

        return redirect()->route('institute.dashboard');
    }

    public function resendOtp()
    {
        if (!session()->has('otp_user_id')) {
            return redirect()->route('login');
        }

        $userId = session('otp_user_id');

        $lastOtp  = LoginOtp::where('user_id', $userId)->latest()->first();
        $platform = PlatformSmsSetting::current();
        $cooldown = $platform?->otp_resend_cooldown_seconds ?? 30;
        $expiryMinutes = $platform?->otp_expiry_minutes ?? 5;

        if ($lastOtp && $lastOtp->created_at->diffInSeconds(now()) < $cooldown) {
            return back()->withErrors([
                'otp' => "Please wait {$cooldown} seconds before requesting a new OTP."
            ]);
        }

        $otp = (string) random_int(100000, 999999);

        LoginOtp::create([
            'user_id'    => $userId,
            'otp'        => Hash::make($otp),
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        $user = User::find($userId);

        Mail::to($user->email)->send(new InstituteOtpMail($user, $otp));

        if ($user->mobile) {
            SmsService::sendOtp($user->mobile, $otp);
        }

        return back()->with('success', 'OTP Send Successfully');

    }

    public function logout()
    {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}