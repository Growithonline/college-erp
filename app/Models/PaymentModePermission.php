<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentModePermission extends Model
{
    protected $fillable = [
        'institute_id', 'user_type', 'user_id',
        'allowed_modes', 'allowed_bank_ids',
    ];

    protected $casts = [
        'allowed_modes'    => 'array',
        'allowed_bank_ids' => 'array',
    ];

    // ── Get permission for a specific user ──────────────────────────────
    public static function forUser(string $type, int $userId): ?self
    {
        return static::where('user_type', $type)
            ->where('user_id', $userId)
            ->first();
    }

    // ── Check if mode is allowed ────────────────────────────────────────
    public function allowsMode(string $mode): bool
    {
        return in_array($mode, $this->allowed_modes ?? []);
    }

    // ── Get allowed bank accounts ───────────────────────────────────────
    public function bankAccounts()
    {
        return InstituteBankAccount::whereIn('id', $this->allowed_bank_ids ?? [])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    // ── Default: all modes, no banks ────────────────────────────────────
    public static function defaultModes(): array
    {
        return ['cash', 'upi', 'cheque', 'dd', 'neft', 'rtgs', 'online'];
    }
}