<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PlatformSmsSetting extends Model
{
    protected $fillable = [
        'provider',
        'api_key',
        'sender_id',
        'otp_expiry_minutes',
        'otp_max_attempts',
        'otp_resend_cooldown_seconds',
        'is_active',
        'custom_endpoint',
        'custom_method',
        'custom_headers_json',
        'custom_body_template',
        'custom_success_key',
        'custom_success_value',
        'custom_credentials_json',
        'otp_message_template',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function current(): ?self
    {
        return self::first();
    }

    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getApiKeyAttribute(?string $value): string
    {
        if (empty($value)) return '';
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return '';
        }
    }

    public function getMaskedApiKeyAttribute(): string
    {
        $key = $this->api_key;
        if (strlen($key) <= 8) return str_repeat('●', strlen($key));
        return substr($key, 0, 4) . str_repeat('●', strlen($key) - 8) . substr($key, -4);
    }

    public function setCustomCredentialsJsonAttribute(?string $value): void
    {
        $this->attributes['custom_credentials_json'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getCustomCredentialsJsonAttribute(?string $value): string
    {
        if (empty($value)) return '{}';
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return '{}';
        }
    }

    public function isUsable(): bool
    {
        if (! $this->is_active) return false;
        if ($this->provider === 'custom') return ! empty($this->custom_endpoint);
        return ! empty($this->api_key);
    }
}
