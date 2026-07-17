<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnquiryEmailOtp extends Model
{
    protected $fillable = ['email', 'otp', 'expires_at', 'is_used', 'attempts'];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used'    => 'boolean',
    ];

    public static function generateFor(string $email): string
    {
        $otp = (string) random_int(100000, 999999);

        static::create([
            'email'      => $email,
            'otp'        => $otp,
            'expires_at' => now()->addMinutes(10),
        ]);

        return $otp;
    }

    public static function attemptVerify(string $email, string $otp): bool
    {
        $record = static::where('email', $email)
            ->where('is_used', false)
            ->where('expires_at', '>=', now())
            ->where('attempts', '<', 5)
            ->latest('id')
            ->first();

        if (!$record || !hash_equals($record->otp, $otp)) {
            $record?->increment('attempts');
            return false;
        }

        $record->update(['is_used' => true]);
        return true;
    }
}
