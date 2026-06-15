<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DomainException;

class CenterWallet extends Model
{
    protected $fillable = [
        'center_id', 'institute_id',
        'total_tokens', 'used_tokens', 'remaining_tokens',
        'expires_at', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'total_tokens'     => 'decimal:2',
        'used_tokens'      => 'decimal:2',
        'remaining_tokens' => 'decimal:2',
        'expires_at'       => 'date',
    ];

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function transactions()
    {
        return $this->hasMany(CenterWalletTransaction::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->startOfDay()->gt($this->expires_at);
    }

    public function getBlockStatus(float $amount = 0): array
    {
        if ($this->status !== 'active') {
            return ['blocked' => true, 'type' => 'suspended', 'reason' => 'Fee collection portal has been suspended by admin.'];
        }
        if ($this->isExpired()) {
            return ['blocked' => true, 'type' => 'expired', 'reason' => 'Fee collection window expired on ' . $this->expires_at->format('d M Y') . '. Request admin to reopen.'];
        }
        if ((float) $this->remaining_tokens <= 0) {
            return ['blocked' => true, 'type' => 'exhausted', 'reason' => 'Token balance is zero. Request admin to add more tokens.'];
        }
        if ($amount > 0 && $amount > (float) $this->remaining_tokens) {
            return ['blocked' => true, 'type' => 'insufficient', 'reason' => 'Only ₹' . number_format($this->remaining_tokens, 0) . ' tokens remaining. Reduce the collection amount to proceed.'];
        }
        return ['blocked' => false, 'type' => null, 'reason' => null];
    }

    public function deduct(float $amount, ?int $invoiceId = null, ?int $createdBy = null): void
    {
        $newRemaining = max(0, (float) $this->remaining_tokens - $amount);
        $this->update([
            'remaining_tokens' => $newRemaining,
            'used_tokens'      => (float) $this->used_tokens + $amount,
        ]);
        $this->transactions()->create([
            'type'           => 'debit',
            'amount'         => $amount,
            'balance_after'  => $newRemaining,
            'fee_invoice_id' => $invoiceId,
            'note'           => 'Fee collection deduction',
            'created_by'     => $createdBy,
        ]);
    }

    public function consumeOrFail(float $amount, ?int $invoiceId = null, ?int $createdBy = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
        $status = $locked->getBlockStatus($amount);

        if ($status['blocked']) {
            throw new DomainException((string) $status['reason']);
        }

        $newRemaining = (float) $locked->remaining_tokens - $amount;
        $locked->update([
            'remaining_tokens' => $newRemaining,
            'used_tokens'      => (float) $locked->used_tokens + $amount,
        ]);
        $locked->transactions()->create([
            'type'           => 'debit',
            'amount'         => $amount,
            'balance_after'  => $newRemaining,
            'fee_invoice_id' => $invoiceId,
            'note'           => 'Fee collection deduction',
            'created_by'     => $createdBy,
        ]);

        $this->refresh();
    }

    public function credit(float $amount, string $note = 'Token top-up', ?int $createdBy = null): void
    {
        $newRemaining = (float) $this->remaining_tokens + $amount;
        $this->update([
            'total_tokens'     => (float) $this->total_tokens + $amount,
            'remaining_tokens' => $newRemaining,
        ]);
        $this->transactions()->create([
            'type'          => 'credit',
            'amount'        => $amount,
            'balance_after' => $newRemaining,
            'note'          => $note,
            'created_by'    => $createdBy,
        ]);
    }

    public function refund(float $amount, ?int $invoiceId = null, ?int $createdBy = null, string $note = 'Fee cancellation refund'): void
    {
        if ($amount <= 0) {
            return;
        }

        $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
        $newRemaining = (float) $locked->remaining_tokens + $amount;
        $locked->update([
            'remaining_tokens' => $newRemaining,
            'used_tokens'      => max(0, (float) $locked->used_tokens - $amount),
        ]);
        $locked->transactions()->create([
            'type'           => 'credit',
            'amount'         => $amount,
            'balance_after'  => $newRemaining,
            'fee_invoice_id' => $invoiceId,
            'note'           => $note,
            'created_by'     => $createdBy,
        ]);

        $this->refresh();
    }
}
