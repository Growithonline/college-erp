<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StreamYearSubjectRule extends Model
{
    protected $fillable = [
        'course_stream_id',
        'year_number',
        'minor_optional_min',
        'minor_optional_max',
        'major_min',
        'major_max',
    ];

    public function stream()
    {
        return $this->belongsTo(CourseStream::class, 'course_stream_id');
    }

    // Helper: is year me subject count change hua pichle year se?
    public function hasFewerMinorsThan(StreamYearSubjectRule $other): bool
    {
        return $this->minor_optional_max < $other->minor_optional_max;
    }
}