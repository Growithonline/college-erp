<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'institute_id',
        'parent_id',
        'code',
        'name',
        'type',
        'normal_side',
        'linked_type',
        'linked_id',
        'meta',
        'is_system',
        'is_active',
        'allow_manual_posting',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'allow_manual_posting' => 'boolean',
    ];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}
