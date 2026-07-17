<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    protected $fillable = [
        'institute_id', 'name', 'mobile', 'email', 'course_id', 'city',
        'status', 'source', 'utm_source', 'utm_medium', 'utm_campaign',
        'assigned_staff_id', 'email_verified_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function institute()     { return $this->belongsTo(Institute::class); }
    public function course()        { return $this->belongsTo(Course::class); }
    public function assignedStaff() { return $this->belongsTo(StaffMember::class, 'assigned_staff_id'); }
    public function followUps()     { return $this->hasMany(EnquiryFollowUp::class)->latest(); }

    public function scopeForInstitute($query, int $instituteId)
    {
        return $query->where('institute_id', $instituteId);
    }
}
