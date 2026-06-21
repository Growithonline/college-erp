<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CourseStreamSubject extends Model
{
    protected $table = 'course_stream_subjects';

    protected $fillable = [
        'course_stream_id', 'subject_id', 'year_number',
        'subject_role', 'is_chooseable', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'course_stream_id' => 'integer',
        'subject_id'       => 'integer',
        'year_number'      => 'integer',
        'sort_order'       => 'integer',
        'is_chooseable'    => 'boolean',
        'is_active'        => 'boolean',
    ];

    public const ROLE_MAJOR      = 'major';
    public const ROLE_MINOR      = 'minor';
    public const ROLE_COMPULSORY = 'compulsory';
    public const ROLE_OPTIONAL   = 'optional';
    public const ROLE_BOTH       = 'both';

    public static function roles(): array
    {
        return [
            self::ROLE_MAJOR      => 'Major',
            self::ROLE_MINOR      => 'Minor',
            self::ROLE_COMPULSORY => 'Compulsory',
            self::ROLE_OPTIONAL   => 'Optional',
            self::ROLE_BOTH       => 'Both (Major + Minor)', // #1
        ];
    }

    public function stream(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CourseStream::class, 'course_stream_id');
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('year_number', $year);
    }

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('subject_role', $role);
    }

    public function scopeChooseable(Builder $query): Builder
    {
        return $query->where('is_chooseable', true);
    }

    public function scopeCompulsory(Builder $query): Builder
    {
        return $query->where('is_chooseable', false);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::roles()[$this->subject_role] ?? ucfirst($this->subject_role);
    }

    public function getRoleBadgeClassAttribute(): string
    {
        return match($this->subject_role) {
            'major'      => 'bg-primary',
            'minor'      => 'bg-info text-dark',
            'compulsory' => 'bg-success',
            'optional'   => 'bg-secondary',
            'both'       => 'bg-purple text-white', // #1
            default      => 'bg-light text-dark',
        };
    }
}