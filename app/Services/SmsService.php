<?php

namespace App\Services;

use App\Models\PlatformSmsSetting;
use App\Models\SmsLog;
use App\Models\SmsProviderSetting;
use App\Services\Sms\Contracts\SmsDriverInterface;
use App\Services\Sms\Drivers\CustomHttpDriver;
use App\Services\Sms\Drivers\Fast2SmsDriver;
use App\Services\Sms\Drivers\Msg91Driver;

class SmsService
{
    // Send OTP using platform's account (super admin configured)
    public static function sendOtp(string $mobile, string $otp): bool
    {
        $settings = PlatformSmsSetting::current();

        if (! $settings || ! $settings->is_active) {
            return false;
        }

        $template = $settings->otp_message_template;
        $message  = $template
            ? str_replace('{otp}', $otp, $template)
            : "Your login OTP is {$otp}. Valid for {$settings->otp_expiry_minutes} minutes. Do not share with anyone.";

        $driver = self::makePlatformDriver($settings);
        $result = $driver->send($mobile, $message, $settings->sender_id);

        SmsLog::create([
            'institute_id'      => null,
            'type'              => SmsLog::TYPE_OTP,
            'mobile'            => $mobile,
            'message'           => $message,
            'provider'          => $settings->provider,
            'sender_id'         => $settings->sender_id,
            'status'            => $result['success'] ? SmsLog::STATUS_SENT : SmsLog::STATUS_FAILED,
            'provider_response' => json_encode($result['response'] ?? $result['error'] ?? null),
        ]);

        return $result['success'];
    }

    // Send notice/bulk SMS using institute's own provider
    public static function sendForInstitute(int $instituteId, string $mobile, string $message, string $type = SmsLog::TYPE_NOTICE): bool
    {
        $settings = SmsProviderSetting::where('institute_id', $instituteId)->first();

        if (! $settings || ! $settings->isUsable()) {
            return false;
        }

        $driver = self::makeInstituteDriver($settings);
        $result = $driver->send($mobile, $message, $settings->sender_id);

        SmsLog::create([
            'institute_id'      => $instituteId,
            'type'              => $type,
            'mobile'            => $mobile,
            'message'           => $message,
            'provider'          => $settings->provider,
            'sender_id'         => $settings->sender_id,
            'status'            => $result['success'] ? SmsLog::STATUS_SENT : SmsLog::STATUS_FAILED,
            'provider_response' => json_encode($result['response'] ?? $result['error'] ?? null),
        ]);

        return $result['success'];
    }

    // Send custom SMS using platform's account (broadcast / welcome / reminders)
    public static function sendFromPlatform(string $mobile, string $message, string $type = SmsLog::TYPE_BROADCAST, ?int $instituteId = null): bool
    {
        $settings = PlatformSmsSetting::current();

        if (! $settings || ! $settings->is_active) {
            return false;
        }

        $driver = self::makePlatformDriver($settings);
        $result = $driver->send($mobile, $message, $settings->sender_id);

        SmsLog::create([
            'institute_id'      => $instituteId,
            'type'              => $type,
            'mobile'            => $mobile,
            'message'           => $message,
            'provider'          => $settings->provider,
            'sender_id'         => $settings->sender_id,
            'status'            => $result['success'] ? SmsLog::STATUS_SENT : SmsLog::STATUS_FAILED,
            'provider_response' => json_encode($result['response'] ?? $result['error'] ?? null),
        ]);

        return $result['success'];
    }

    // Check if platform SMS is usable
    public static function isPlatformConfigured(): bool
    {
        $settings = PlatformSmsSetting::current();
        if (! $settings || ! $settings->is_active) return false;
        if ($settings->provider === 'custom') return ! empty($settings->custom_endpoint);
        return ! empty($settings->api_key);
    }

    // Test a provider connection (without saving)
    public static function testConnection(string $provider, string $apiKey, string $senderId, string $customEndpoint = ''): array
    {
        if ($provider === 'custom') {
            return self::testCustomEndpoint($customEndpoint);
        }
        $driver = self::makeDriver($provider, $apiKey);
        return $driver->testConnection($apiKey, $senderId);
    }

    // Test reachability of a custom HTTP endpoint
    public static function testCustomEndpoint(string $url): array
    {
        if (empty($url)) {
            return ['success' => false, 'error' => 'Endpoint URL daalo pehle.'];
        }
        $parsed = parse_url($url);
        if (empty($parsed['host'])) {
            return ['success' => false, 'error' => 'Valid URL format nahi hai.'];
        }
        try {
            $baseUrl = ($parsed['scheme'] ?? 'https') . '://' . $parsed['host'];
            \Illuminate\Support\Facades\Http::withOptions(['connect_timeout' => 5])->timeout(8)->get($baseUrl);
            return [
                'success' => true,
                'message' => 'Server reachable hai. Credentials verify karne ke liye ek real SMS bhejo.',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Server unreachable: ' . $e->getMessage()];
        }
    }

    // Check balance for an institute's configured provider
    public static function checkInstituteBalance(int $instituteId): array
    {
        $settings = SmsProviderSetting::where('institute_id', $instituteId)->first();

        if (! $settings || ! $settings->isUsable()) {
            return ['success' => false, 'error' => 'SMS not configured'];
        }

        $driver = self::makeInstituteDriver($settings);
        return $driver->checkBalance();
    }

    // Check balance for platform (super admin)
    public static function checkPlatformBalance(): array
    {
        $settings = PlatformSmsSetting::current();

        if (! $settings || ! $settings->isUsable()) {
            return ['success' => false, 'error' => 'Platform SMS not configured'];
        }

        $driver = self::makePlatformDriver($settings);
        return $driver->checkBalance();
    }

    public static function isInstituteConfigured(int $instituteId): bool
    {
        $settings = SmsProviderSetting::where('institute_id', $instituteId)->first();
        return $settings && $settings->isUsable();
    }

    private static function makePlatformDriver(PlatformSmsSetting $settings): SmsDriverInterface
    {
        if ($settings->provider === 'custom') {
            return new CustomHttpDriver(
                endpoint:        $settings->custom_endpoint         ?? '',
                method:          $settings->custom_method           ?? 'POST',
                headersJson:     $settings->custom_headers_json     ?? '',
                bodyTemplate:    $settings->custom_body_template    ?? '',
                successKey:      $settings->custom_success_key      ?? '',
                successValue:    $settings->custom_success_value    ?? '',
                apiKey:          $settings->api_key                 ?? '',
                credentialsJson: $settings->custom_credentials_json ?? '{}',
            );
        }
        return self::makeDriver($settings->provider, $settings->api_key);
    }

    private static function makeInstituteDriver(SmsProviderSetting $settings): SmsDriverInterface
    {
        if ($settings->provider === 'custom') {
            return new CustomHttpDriver(
                endpoint:        $settings->custom_endpoint        ?? '',
                method:          $settings->custom_method          ?? 'POST',
                headersJson:     $settings->custom_headers_json    ?? '',
                bodyTemplate:    $settings->custom_body_template   ?? '',
                successKey:      $settings->custom_success_key     ?? '',
                successValue:    $settings->custom_success_value   ?? '',
                apiKey:          $settings->api_key                ?? '',
                credentialsJson: $settings->custom_credentials_json ?? '{}',
            );
        }
        return self::makeDriver($settings->provider, $settings->api_key);
    }

    private static function makeDriver(string $provider, string $apiKey): SmsDriverInterface
    {
        return match ($provider) {
            'fast2sms' => new Fast2SmsDriver($apiKey),
            default    => new Msg91Driver($apiKey),
        };
    }
}
