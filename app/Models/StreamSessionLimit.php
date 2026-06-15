<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StreamSessionLimit extends Model
{
    protected $fillable = ['course_stream_id', 'academic_session_id', 'student_limit'];

    public function stream()
    {
        return $this->belongsTo(CourseStream::class, 'course_stream_id');
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }
}