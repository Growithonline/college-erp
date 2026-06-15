<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'institute_id',
        'student_id',
        'certificate_type_id',
        'academic_session_id',
        'certificate_number',
        'status',
        'remarks',
        'issued_by',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function certificateType()
    {
        return $this->belongsTo(CertificateType::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function scopeForInstitute($query, int $instituteId)
    {
        return $query->where('institute_id', $instituteId);
    }
}
