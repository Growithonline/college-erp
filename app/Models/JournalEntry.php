<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'institute_id',
        'academic_session_id',
        'date',
        'entry_key',
        'reference_type',
        'reference_id',
        'status',
        'narration',
        'total_debit',
        'total_credit',
        'reversal_of_entry_id',
        'posted_at',
        'reversed_at',
        'created_by',
        'created_by_role',
        'reversed_by_user_id',
        'meta',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'meta' => 'array',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_entry_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_entry_id');
    }
}
