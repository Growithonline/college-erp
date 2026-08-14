<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ExpenseVendorWorkOrder extends Model
{
    use BelongsToAuthInstitute;

    protected $fillable = [
        'institute_id', 'expense_vendor_id', 'title', 'description', 'status',
        'notes', 'created_by', 'closed_by', 'closed_at',
    ];

    protected $casts = [
        'total_budget'     => 'decimal:2',
        'total_spent'      => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'closed_at'        => 'datetime',
    ];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(ExpenseVendor::class, 'expense_vendor_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ExpenseVendorWorkOrderTransaction::class, 'expense_vendor_work_order_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_vendor_work_order_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function isOverBudget(): bool
    {
        return (float) $this->remaining_amount < 0;
    }

    public function overBudgetAmount(): float
    {
        return $this->isOverBudget() ? abs((float) $this->remaining_amount) : 0.0;
    }

    public function credit(float $amount, string $note = 'Budget top-up', ?int $createdBy = null): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($amount, $note, $createdBy) {
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            $newRemaining = (float) $locked->remaining_amount + $amount;
            // total_budget/remaining_amount are deliberately excluded from $fillable
            // (never mass-assignable from a controller/form) — forceFill() is required
            // here since this is the one trusted internal write path allowed to touch them.
            $locked->forceFill([
                'total_budget'     => (float) $locked->total_budget + $amount,
                'remaining_amount' => $newRemaining,
            ])->save();
            $locked->transactions()->create([
                'type'          => 'credit',
                'amount'        => $amount,
                'balance_after' => $newRemaining,
                'note'          => $note,
                'created_by'    => $createdBy,
            ]);
        });

        $this->refresh();
    }

    public function debit(float $amount, ?int $expenseId = null, string $note = 'Expense debit', ?int $createdBy = null): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($amount, $expenseId, $note, $createdBy) {
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            // No floor here — remaining_amount is allowed to go negative (over-budget),
            // it's a soft warning signal, not a hard block.
            $newRemaining = (float) $locked->remaining_amount - $amount;
            $locked->forceFill([
                'total_spent'      => (float) $locked->total_spent + $amount,
                'remaining_amount' => $newRemaining,
            ])->save();
            $locked->transactions()->create([
                'type'          => 'debit',
                'amount'        => $amount,
                'balance_after' => $newRemaining,
                'expense_id'    => $expenseId,
                'note'          => $note,
                'created_by'    => $createdBy,
            ]);
        });

        $this->refresh();
    }

    public function creditBack(float $amount, ?int $expenseId = null, string $note = 'Expense reversed', ?int $createdBy = null): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($amount, $expenseId, $note, $createdBy) {
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            $newRemaining = (float) $locked->remaining_amount + $amount;
            $locked->forceFill([
                'total_spent'      => max(0, (float) $locked->total_spent - $amount),
                'remaining_amount' => $newRemaining,
            ])->save();
            $locked->transactions()->create([
                'type'          => 'credit',
                'amount'        => $amount,
                'balance_after' => $newRemaining,
                'expense_id'    => $expenseId,
                'note'          => $note,
                'created_by'    => $createdBy,
            ]);
        });

        $this->refresh();
    }

    public function close(?int $closedBy = null): void
    {
        $this->update([
            'status'    => 'closed',
            'closed_by' => $closedBy,
            'closed_at' => now(),
        ]);
    }
}
