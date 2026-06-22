<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'institute_id',
        'course_type_id',
        'name',
        'code',
        'duration',
        'duration_type',
        'structure_type',
        'semesters_per_year',
        'max_atkt_allowed',
        'lateral_entry_allowed',
        'lateral_entry_start_part',
        'status',
    ];

    protected $casts = [
        'lateral_entry_allowed' => 'boolean',
        'semesters_per_year'    => 'integer',
        'status'                => 'boolean',
    ];

    // Effective semesters per year — safe fallback for existing courses
    public function effectiveSemestersPerYear(): int
    {
        $spy = (int) ($this->semesters_per_year ?? 0);
        if ($spy > 0) {
            return $spy;
        }
        return match ($this->structure_type) {
            'yearly'   => 1,
            'modular'  => 1,
            'trimester'=> 3,
            default    => 2,
        };
    }

    // Semester dropdown options for fee forms — value => label
    public function semesterOptions(): array
    {
        $spy = $this->effectiveSemestersPerYear();

        if ($spy <= 1) {
            return [0 => 'Annual'];
        }

        $partLabel = $this->structure_type === 'trimester' ? 'Trimester' : 'Sem';
        $allLabel  = $spy > 2 ? 'All' : 'Both';

        $options = [0 => $allLabel];
        for ($i = 1; $i <= $spy; $i++) {
            $options[$i] = $partLabel . ' ' . $i;
        }
        return $options;
    }

    // Human-readable label for a stored semester value
    public function semesterLabel(int $value): string
    {
        return $this->semesterOptions()[$value] ?? 'Sem ' . $value;
    }

    // Short-term courses (certificate/modular) have no semester promotion
    public function isShortTerm(): bool
    {
        return $this->structure_type === 'modular';
    }

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function type()
    {
        return $this->belongsTo(CourseType::class, 'course_type_id');
    }

    public function parts()
    {
        return $this->hasMany(CoursePart::class)->orderBy('part_number');
    }

    public function streams()
    {
        return $this->hasMany(CourseStream::class);
    }
}