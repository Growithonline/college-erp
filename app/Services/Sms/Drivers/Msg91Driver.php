<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsDriverInterface;
use Illuminate\Support\Facades\Http;

class Msg91Driver implements SmsDriverInterface
{
    public function __construct(private string $apiKey) {}

    public function send(string $mobile, string $message, string $senderId): array
    {
        $mobile = $this->normalizeMobile($mobile);

        try {
            $response = Http::timeout(10)
                ->withHeaders(['authkey' => $this->apiKey])
                ->get('https://api.msg91.com/api/sendhttp.php', [
                    'mobiles'  => $mobile,
                    'message'  => $message,
                    'sender'   => $senderId,
                    'route'    => 4, // transactional
                    'country'  => 91,
                    'response' => 'json',
                ]);

            $body = $response->json();

            if ($response->successful() && isset($body['type']) && $body['type'] === 'success') {
                return ['success' => true, 'response' => $body];
            }

            return ['success' => false, 'response' => $body, 'error' => $body['message'] ?? 'Failed'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function checkBalance(): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['authkey' => $this->apiKey])
                ->get('https://api.msg91.com/api/balance.php', ['type' => 1]);

            if ($response->successful()) {
                $balance = trim($response->body());
                return ['success' => true, 'balance' => $balance, 'currency' => '₹'];
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
                ->withHeaders(['authkey' => $apiKey])
                ->get('https://api.msg91.com/api/balance.php', ['type' => 1]);

            if ($response->successful() && is_numeric(trim($response->body()))) {
                return [
                    'success' => true,
                    'message' => 'Connected successfully',
                    'balance' => trim($response->body()) . ' credits',
                ];
            }

            return ['success' => false, 'error' => 'Invalid API key or connection failed'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function normalizeMobile(string $mobile): string
    {
        $mobile = preg_replace('/\D/', '', $mobile);
        if (strlen($mobile) === 10) {
            return '91' . $mobile;
        }
        return $mobile;
    }
}
