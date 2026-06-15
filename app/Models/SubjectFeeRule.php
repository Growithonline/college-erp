<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectFeeRule extends Model
{
    protected $fillable = [
        'institute_id', 'academic_session_id',
        'course_id', 'subject_id',
        'course_part', 'semester',
        'subject_fee', 'practical_fee',
        'is_active',
    ];

    protected $casts = [
        'subject_fee'   => 'decimal:2',
        'practical_fee' => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    public function subject()  { return $this->belongsTo(Subject::class); }
    public function course()   { return $this->belongsTo(Course::class); }
    public function session()  { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }
    public function institute(){ return $this->belongsTo(Institute::class); }

    public function getTotalFeeAttribute(): float
    {
        return (float)$this->subject_fee + (float)$this->practical_fee;
    }
}
