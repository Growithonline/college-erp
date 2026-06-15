<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StudentType extends Model
{
    protected $fillable = [
        'institute_id', 'name', 'slug', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Slug auto-generate ────────────────────────────────────────────────
    public static function makeSlug(string $name): string
    {
        return Str::slug($name, '_');
    }

    // ── Name enforce Title Case ───────────────────────────────────────────
    public static function normalizeName(string $name): string
    {
        return ucwords(strtolower(trim($name)));
    }

    // ── Scopes ────────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForInstitute($query, int $instituteId)
    {
        return $query->where('institute_id', $instituteId);
    }

    // ── Relationships ─────────────────────────────────────────────────────
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }
}
