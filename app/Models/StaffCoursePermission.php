<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffCoursePermission extends Model
{
    protected $fillable = ['staff_member_id', 'course_id'];

    public function staff()
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
