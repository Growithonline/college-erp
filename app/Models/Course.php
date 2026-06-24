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

    // Semester options for a specific year — absolute values (e.g. Year 2 trimester: T4, T5, T6)
    // Year 0 = All years → relative options (T1, T2, T3)
    public function semesterOptionsForYear(int $yearNumber): array
    {
        $spy = $this->effectiveSemestersPerYear();

        if ($spy <= 1) {
            return [0 => 'Annual'];
        }

        $partLabel = $this->structure_type === 'trimester' ? 'Trimester' : 'Sem';
        $allLabel  = $spy > 2 ? 'All' : 'Both';

        if ($yearNumber === 0) {
            // Year = All → relative (T1, T2, T3) — means "this trimester of every year"
            return $this->semesterOptions();
        }

        // Year specific → absolute semesters for that year
        $options = [0 => $allLabel];
        $start   = ($yearNumber - 1) * $spy + 1;
        $end     = $yearNumber * $spy;
        for ($i = $start; $i <= $end; $i++) {
            $options[$i] = $partLabel . ' ' . $i;
        }
        return $options;
    }

    // All semester options as JS-ready array indexed by year_number
    // Used by fee structure form to populate Sem dropdown dynamically
    public function allSemesterOptionsByYear(): array
    {
        $duration = (int) ($this->duration ?? 1);
        $data     = [0 => $this->semesterOptions()]; // Year=All → relative
        for ($y = 1; $y <= $duration; $y++) {
            $data[$y] = $this->semesterOptionsForYear($y);
        }
        return $data;
    }

    // Human-readable label for a stored semester value
    // course_part=0 → stored value is relative; course_part>0 → stored value is absolute
    public function semesterLabel(int $value, int $coursePart = 0): string
    {
        if ($value === 0) {
            $spy = $this->effectiveSemestersPerYear();
            return $spy > 2 ? 'All' : ($spy === 1 ? 'Annual' : 'Both');
        }

        $partLabel = $this->structure_type === 'trimester' ? 'Trimester' : 'Sem';
        return $partLabel . ' ' . $value;
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