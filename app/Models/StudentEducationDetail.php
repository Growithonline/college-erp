<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StudentEducationDetail extends Model
{
    protected $fillable = [
        'student_id', 'exam_name', 'education_stream', 'institute_name', 'board_university',
        'roll_number', 'passing_year', 'district', 'division',
        'obtained_marks', 'max_marks', 'percentage',
    ];

    public function student() { return $this->belongsTo(Student::class); }
}
