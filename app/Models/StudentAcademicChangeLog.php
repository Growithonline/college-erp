<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAcademicChangeLog extends Model
{
    protected $fillable = [
        'student_id',
        'institute_id',
        'academic_session_id',
        'old_snapshot',
        'new_snapshot',
        'old_academic_fee',
        'new_academic_fee',
        'fee_delta',
        'wallet_balance_after',
        'actor_type',
        'actor_name',
        'reason',
        'notes',
    ];

    protected $casts = [
        'old_snapshot' => 'array',
        'new_snapshot' => 'array',
        'old_academic_fee' => 'decimal:2',
        'new_academic_fee' => 'decimal:2',
        'fee_delta' => 'decimal:2',
        'wallet_balance_after' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }
}
