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
        'max_atkt_allowed',
        'lateral_entry_allowed',
        'lateral_entry_start_part',
        'status',
    ];

    protected $casts = [
        'lateral_entry_allowed' => 'boolean',
        'status'                => 'boolean',
    ];

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