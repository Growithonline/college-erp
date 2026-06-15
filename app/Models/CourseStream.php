<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseStream extends Model
{
    protected $fillable = [
        'course_id',
        'name',
        'code',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────
    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function yearRules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StreamYearSubjectRule::class)->orderBy('year_number');
    }

    public function feeAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FeeAssignment::class);
    }

    // ── Subject Mapping ──────────────────────────────────────────────────
    public function streamSubjects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CourseStreamSubject::class)->orderBy('sort_order');
    }

    public function subjects(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'course_stream_subjects')
                    ->withPivot(['year_number', 'subject_role', 'is_chooseable', 'sort_order', 'is_active'])
                    ->withTimestamps();
    }

    // Specific year ke subjects
    public function subjectsForYear(int $year): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'course_stream_subjects')
                    ->wherePivot('year_number', $year)
                    ->wherePivot('is_active', true)
                    ->withPivot(['year_number', 'subject_role', 'is_chooseable', 'sort_order'])
                    ->orderByPivot('sort_order');
    }

    // Compulsory subjects (auto-include in admission)
    public function compulsorySubjectsForYear(int $year): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'course_stream_subjects')
                    ->wherePivot('year_number', $year)
                    ->wherePivot('is_active', true)
                    ->wherePivot('is_chooseable', false)
                    ->withPivot(['year_number', 'subject_role', 'is_chooseable', 'sort_order'])
                    ->orderByPivot('sort_order');
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    public function getRuleForYear(int $year): ?StreamYearSubjectRule
    {
        return $this->yearRules()->where('year_number', $year)->first();
    }

    public function getTotalSubjectsCountAttribute(): int
    {
        return $this->streamSubjects()->where('is_active', true)->count();
    }
}