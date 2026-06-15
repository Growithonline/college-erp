<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeAssignment extends Model
{
    protected $fillable = [
        'institute_id',
        'fee_type_id',
        'academic_session_id',
        'applies_to',
        'course_stream_id',
        'course_part_id',
        'subject_component_id',
        'amount',
        'is_active',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function stream()
    {
        return $this->belongsTo(CourseStream::class, 'course_stream_id');
    }

    public function coursePart()
    {
        return $this->belongsTo(CoursePart::class);
    }

    public function subjectComponent()
    {
        return $this->belongsTo(SubjectComponent::class);
    }
}
