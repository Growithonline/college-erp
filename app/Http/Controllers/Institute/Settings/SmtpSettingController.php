<?php

namespace App\Http\Controllers\Institute\Settings;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SmtpSettingController extends Controller
{
    private function institute(): Institute
    {
        return Institute::findOrFail(Auth::user()->institute_id);
    }

    public function index()
    {
        $institute = $this->institute();
        return view('institute.settings.smtp', compact('institute'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'smtp_host'       => ['required', 'string', 'max:255', function ($attr, $value, $fail) {
                if (in_array(strtolower($value), ['localhost', '::1', '0.0.0.0'])) {
                    $fail('This SMTP host is not allowed.');
                    return;
                }
                // If it's a literal IP, block private/reserved ranges immediately
                if (filter_var($value, FILTER_VALIDATE_IP)) {
                    if (! filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        $fail('Private or reserved IP addresses are not allowed as SMTP host.');
                    }
                    return;
                }
                // Resolve hostname and check the resulting IP
                $ip = gethostbyname($value);
                if ($ip !== $value && filter_var($ip, FILTER_VALIDATE_IP)) {
                    if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        $fail('This SMTP host resolves to a private or reserved address and is not allowed.');
                    }
                }
            }],
            'smtp_port'       => 'required|integer|between:1,65535',
            'smtp_encryption' => 'required|in:tls,ssl,none',
            'smtp_username'   => 'required|string|max:255',
            'smtp_password'   => 'nullable|string|max:500',
            'smtp_from_name'  => 'required|string|max:100',
            'smtp_from_email' => 'required|email|max:255',
        ]);

        $institute = $this->institute();

        $data = [
            'smtp_host'       => $request->smtp_host,
            'smtp_port'       => $request->smtp_port,
            'smtp_encryption' => $request->smtp_encryption,
            'smtp_username'   => $request->smtp_username,
            'smtp_from_name'  => $request->smtp_from_name,
            'smtp_from_email' => $request->smtp_from_email,
            'smtp_verified'   => false, // reset on config change
        ];

        // Only update password if a new one is provided
        if (filled($request->smtp_password)) {
            $data['smtp_password'] = $request->smtp_password;
        }

        $institute->update($data);

        return back()->with('success', 'SMTP settings saved. Please test the connection to verify.');
    }

    public function testConnection(Request $request)
    {
        $institute = $this->institute();

        if (! filled($institute->smtp_host) || ! filled($institute->smtp_username)) {
            return back()->with('error', 'Please save your SMTP settings first before testing.');
        }

        try {
            $mailerKey = 'inst_smtp_test_' . $institute->id;
            $config = [
                'transport'  => 'smtp',
                'host'       => $institute->smtp_host,
                'port'       => $institute->smtp_port,
                'encryption' => $institute->smtp_encryption === 'none' ? null : $institute->smtp_encryption,
                'username'   => $institute->smtp_username,
                'password'   => $institute->smtp_password,
                'timeout'    => 10,
            ];

            config(['mail.mailers.' . $mailerKey => $config]);

            Mail::mailer($mailerKey)
                ->to(Auth::user()->email)
                ->send(new \App\Mail\SmtpTestMail($institute->smtp_from_name, $institute->smtp_from_email));

            $institute->update(['smtp_verified' => true]);

            return back()->with('success', 'Connection successful! A test email was sent to ' . Auth::user()->email);

        } catch (\Throwable $e) {
            $institute->update(['smtp_verified' => false]);
            \Log::warning('SMTP test failed', ['institute_id' => $institute->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Connection failed. Please check your host, port, and credentials.');
        }
    }

    public function disconnect()
    {
        $this->institute()->update([
            'smtp_host'       => null,
            'smtp_port'       => 587,
            'smtp_encryption' => 'tls',
            'smtp_username'   => null,
            'smtp_password'   => null,
            'smtp_from_name'  => null,
            'smtp_from_email' => null,
            'smtp_verified'   => false,
        ]);

        return back()->with('success', 'SMTP configuration removed. Emails will now use the platform default.');
    }
}
