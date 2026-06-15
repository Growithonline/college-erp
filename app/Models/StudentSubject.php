<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class StudentSubject extends Model
{
    protected $table = 'student_subjects';

    protected $fillable = [
        'student_id',
        'subject_id',
        'academic_session_id',
        'year_number',
        'subject_role',
        'is_auto_included',
    ];

    protected $casts = [
        'year_number'      => 'integer',
        'is_auto_included' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────
    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function session(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────
    public function scopeForSession(Builder $query, int $sessionId): Builder
    {
        return $query->where('academic_session_id', $sessionId);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('year_number', $year);
    }

    public function scopeMajor(Builder $query): Builder
    {
        return $query->where('subject_role', 'major');
    }

    public function scopeMinor(Builder $query): Builder
    {
        return $query->where('subject_role', 'minor');
    }

    public function scopeCompulsory(Builder $query): Builder
    {
        return $query->where('subject_role', 'compulsory');
    }
}