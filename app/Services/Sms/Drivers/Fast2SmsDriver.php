<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsDriverInterface;
use Illuminate\Support\Facades\Http;

class Fast2SmsDriver implements SmsDriverInterface
{
    public function __construct(private string $apiKey) {}

    public function send(string $mobile, string $message, string $senderId): array
    {
        $mobile = preg_replace('/\D/', '', $mobile);
        if (strlen($mobile) > 10) {
            $mobile = substr($mobile, -10);
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['authorization' => $this->apiKey])
                ->post('https://www.fast2sms.com/dev/bulkV2', [
                    'route'    => 'q', // transactional
                    'message'  => $message,
                    'numbers'  => $mobile,
                    'sender_id' => $senderId,
                ]);

            $body = $response->json();

            if ($response->successful() && isset($body['return']) && $body['return'] === true) {
                return ['success' => true, 'response' => $body];
            }

            $message = $body['message'] ?? 'Failed';
            return ['success' => false, 'response' => $body, 'error' => is_array($message) ? implode(', ', $message) : (string) $message];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Dedicated DLT OTP route — required by Fast2SMS for OTP delivery instead of the
    // generic bulkV2/route=q endpoint. Fast2SMS fills in its own DLT-approved template
    // server-side based on otp_id; the message text is not sent by us.
    public function sendOtp(string $mobile, string $otp, string $otpId): array
    {
        $mobile = preg_replace('/\D/', '', $mobile);
        if (strlen($mobile) > 10) {
            $mobile = substr($mobile, -10);
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['authorization' => $this->apiKey])
                ->post('https://www.fast2sms.com/dev/otp/send', [
                    'mobile' => $mobile,
                    'otp_id' => $otpId,
                    'otp'    => $otp,
                ]);

            $body = $response->json();

            if ($response->successful() && isset($body['return']) && $body['return'] === true) {
                return ['success' => true, 'response' => $body];
            }

            $message = $body['message'] ?? 'Failed';
            return ['success' => false, 'response' => $body, 'error' => is_array($message) ? implode(', ', $message) : (string) $message];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Dedicated DLT bulk route — required for DLT-compliant delivery of non-OTP templated
    // messages (notices, fee alerts, admission, promotion). route=q ("Quick SMS") skips DLT
    // matching entirely and is only manually approved / best-effort, not reliable for scale.
    // $templateId is Fast2SMS's numeric "Message ID" for the pre-approved template;
    // $variablesValues is the pipe-separated ("val1|val2|val3") ordered variable values.
    public function sendDlt(string $mobile, string $templateId, string $variablesValues, string $senderId): array
    {
        $mobile = preg_replace('/\D/', '', $mobile);
        if (strlen($mobile) > 10) {
            $mobile = substr($mobile, -10);
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['authorization' => $this->apiKey])
                ->post('https://www.fast2sms.com/dev/bulkV2', [
                    'route'            => 'dlt',
                    'sender_id'        => $senderId,
                    'message'          => $templateId,
                    'variables_values' => $variablesValues,
                    'numbers'          => $mobile,
                ]);

            $body = $response->json();

            if ($response->successful() && isset($body['return']) && $body['return'] === true) {
                return ['success' => true, 'response' => $body];
            }

            $message = $body['message'] ?? 'Failed';
            return ['success' => false, 'response' => $body, 'error' => is_array($message) ? implode(', ', $message) : (string) $message];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function checkBalance(): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['authorization' => $this->apiKey])
                ->get('https://www.fast2sms.com/dev/wallet');

            $body = $response->json();

            if ($response->successful() && isset($body['wallet'])) {
                return ['success' => true, 'balance' => '₹' . $body['wallet'], 'currency' => '₹'];
            }

            return ['success' => false, 'error' => 'Could not fetch balance'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function testConnection(string $apiKey, string $senderId): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['authorization' => $apiKey])
                ->get('https://www.fast2sms.com/dev/wallet');

            $body = $response->json();

            if ($response->successful() && isset($body['wallet'])) {
                return [
                    'success' => true,
                    'message' => 'Connected successfully',
                    'balance' => '₹' . $body['wallet'],
                ];
            }

            $message = $body['message'] ?? 'Invalid API key';
            return ['success' => false, 'error' => is_array($message) ? ($message[0] ?? 'Invalid API key') : (string) $message];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
