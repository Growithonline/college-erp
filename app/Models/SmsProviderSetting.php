<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SmsProviderSetting extends Model
{
    protected $fillable = [
        'institute_id',
        'provider',
        'api_key',
        'sender_id',
        'is_active',
        'is_sms_disabled',
        'custom_endpoint',
        'custom_method',
        'custom_headers_json',
        'custom_body_template',
        'custom_success_key',
        'custom_success_value',
        'custom_credentials_json',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'is_sms_disabled'  => 'boolean',
    ];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key'] = $value !== null ? Crypt::encryptString($value) : null;
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

    public function setCustomCredentialsJsonAttribute(?string $value): void
    {
        $this->attributes['custom_credentials_json'] = filled($value) ? Crypt::encryptString($value) : null;
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

    public function getMaskedApiKeyAttribute(): string
    {
        $key = $this->api_key;
        if (strlen($key) <= 8) return str_repeat('●', strlen($key));
        return substr($key, 0, 4) . str_repeat('●', strlen($key) - 8) . substr($key, -4);
    }

    public function isUsable(): bool
    {
        if (! $this->is_active || $this->is_sms_disabled) {
            return false;
        }
        if ($this->provider === 'custom') {
            return ! empty($this->custom_endpoint);
        }
        return ! empty($this->api_key);
    }
}
