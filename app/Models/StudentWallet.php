<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentWallet extends Model
{
    protected $fillable = ['student_id', 'institute_id', 'academic_session_id', 'main_b'];

    public function student()   { return $this->belongsTo(Student::class); }
    public function institute() { return $this->belongsTo(Institute::class); }
    public function session()   { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }

    public function transactions()
    {
        return $this->hasMany(StudentTransaction::class, 'student_id', 'student_id')
                    ->where('academic_session_id', $this->academic_session_id);
    }
}