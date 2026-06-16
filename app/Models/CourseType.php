<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CourseType extends Model
{
    protected $fillable = ['institute_id', 'name', 'sort_order', 'is_active', 'education_level'];

    public const EDUCATION_LEVELS = [
        'ug'          => 'UG (10th + 12th required)',
        'pg'          => 'PG (10th + 12th + Graduation required)',
        'diploma'     => 'Diploma (10th + 12th)',
        'certificate' => 'Certificate (10th only)',
        'phd'         => 'PhD (10th + 12th + Graduation)',
        'other'       => 'Other (show all)',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Name normalize ────────────────────────────────────────────────────
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
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }
}
