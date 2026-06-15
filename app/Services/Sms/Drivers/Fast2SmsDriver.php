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

            return ['success' => false, 'response' => $body, 'error' => implode(', ', $body['message'] ?? ['Failed'])];
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

            return ['success' => false, 'error' => $body['message'][0] ?? 'Invalid API key'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
