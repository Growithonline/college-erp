<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionLog extends Model
{
    protected $fillable = [
        'institute_id', 'student_id', 'promotion_type',
        'from_session_id', 'from_course_part_id', 'from_semester',
        'to_session_id',   'to_course_part_id',   'to_semester',
        'dues_carried_forward', 'carry_forward_context', 'status', 'terminal_status', 'remarks',
        'promoted_by', 'promoted_by_role',
        'is_reversed', 'reversed_by_log_id', 'reversed_at', 'reversed_by',
    ];

    protected $casts = [
        'dues_carried_forward' => 'decimal:2',
        'carry_forward_context' => 'array',
        'is_reversed'          => 'boolean',
        'reversed_at'          => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function fromSession()
    {
        return $this->belongsTo(AcademicSession::class, 'from_session_id');
    }

    public function toSession()
    {
        return $this->belongsTo(AcademicSession::class, 'to_session_id');
    }

    public function fromCoursePart()
    {
        return $this->belongsTo(CoursePart::class, 'from_course_part_id');
    }

    public function toCoursePart()
    {
        return $this->belongsTo(CoursePart::class, 'to_course_part_id');
    }
}
