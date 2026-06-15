<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursePart extends Model
{
    protected $fillable = [
        'course_id',
        'part_number',
        'part_name',
        'year_number',
        'status',
    ];

    /**
     * "1st Year", "2nd Year", "3rd Year" etc.
     */
    public function getYearLabelAttribute(): string
    {
        $n = (int) ($this->year_number ?? 1);
        $suffix = match(true) {
            $n === 1 => 'st',
            $n === 2 => 'nd',
            $n === 3 => 'rd',
            default  => 'th',
        };
        return "{$n}{$suffix} Year";
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'course_part_subject')
                    ->withPivot('subject_role')
                    ->withTimestamps();
    }
}