<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Institute extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_uid',
        'name',
        'short_name',       // BBA, SGHPG, DPGC — for Student ID
        'mobile',
        'email',
        'image',
        'address',
        'city',
        'state',
        'pincode',
        'owner_name',
        'owner_mobile',
        'owner_email',
        'owner_whatsapp',
        'owner_address',
        'owner_identity_proof',
        'student_limit',
        'subscription_start',
        'subscription_end',
        'status',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function academicSessions()
    {
        return $this->hasMany(AcademicSession::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    // Helper: short_name se Student ID prefix
    public function getStudentPrefix(): string
    {
        return strtoupper($this->short_name ?? substr($this->name, 0, 3));
    }
}
