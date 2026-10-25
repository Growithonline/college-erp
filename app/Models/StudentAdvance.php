<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAdvance extends Model
{
    public const CARRIED  = 'carried';
    public const REVERSED = 'reversed';

    protected $fillable = [
        'institute_id', 'student_id', 'from_session_id', 'to_session_id',
        'promotion_log_id', 'amount', 'status',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public function student()     { return $this->belongsTo(Student::class); }
    public function fromSession() { return $this->belongsTo(AcademicSession::class, 'from_session_id'); }
    public function toSession()   { return $this->belongsTo(AcademicSession::class, 'to_session_id'); }
}
