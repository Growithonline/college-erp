<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Student extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'institute_id', 'academic_session_id', 'student_uid', 'institute_form_no',
        'sr_no', 'enrollment_no', 'roll_no', 'exam_form_no', 'uin_no', 'reference_no',
        'admission_type', 'admission_source', 'admission_source_id',
        'gap_year', 'admission_date', 'submitted_date',
        'name', 'mobile', 'email', 'dob', 'gender',
        'religion', 'category', 'special_category',
        'nationality', 'student_type', 'aadhar_no', 'apaar_no', 'marital_status',
        'father_name', 'father_mobile', 'father_occupation',
        'mother_name', 'mother_mobile', 'mother_occupation',
        'guardian_name', 'guardian_mobile', 'guardian_relation',
        'perm_village', 'perm_post', 'perm_thana', 'perm_district',
        'perm_state', 'perm_pincode', 'perm_address',
        'comm_same_as_perm', 'comm_state', 'comm_district',
        'comm_post', 'comm_thana', 'comm_pincode', 'comm_city', 'comm_address',
        'course_type_id', 'course_stream_id', 'course_part_id', 'current_semester',
        'photo', 'status',
        'password', 'portal_enabled', 'first_login',
        'has_scholarship', 'scholarship_name', 'scholarship_type',
        'scholarship_authority', 'scholarship_applied_date',
        'scholarship_amount', 'scholarship_ref_no',
        'is_quick_admission',
        'admitted_by_staff_id',
        'approved_by_staff_id', 'approved_by_name', 'approved_at', 'approval_notes',
        'status_reason',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'gap_year'            => 'boolean',
        'is_quick_admission'  => 'boolean',
        'comm_same_as_perm'   => 'boolean',
        'admission_date'    => 'date',
        'submitted_date'    => 'date',
        'dob'               => 'date',
        'has_scholarship'   => 'boolean',
        'scholarship_applied_date' => 'date',
        'scholarship_amount'       => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────
    public function institute()        { return $this->belongsTo(Institute::class); }
    public function session()          { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }
    public function stream()           { return $this->belongsTo(CourseStream::class, 'course_stream_id'); }
    public function coursePart()       { return $this->belongsTo(CoursePart::class, 'course_part_id'); }
    public function admittedBy()       { return $this->belongsTo(\App\Models\StaffMember::class, 'admitted_by_staff_id'); }
    public function approvedByStaff()  { return $this->belongsTo(\App\Models\StaffMember::class, 'approved_by_staff_id'); }

    public function academicIdentities()
    {
        return $this->hasMany(\App\Models\StudentAcademicIdentity::class);
    }

    public function currentAcademicIdentity()
    {
        return $this->hasOne(\App\Models\StudentAcademicIdentity::class)
                    ->where('academic_session_id', $this->academic_session_id)
                    ->where('semester_at_time', $this->current_semester)
                    ->whereNotIn('source', \App\Models\StudentAcademicIdentity::SNAPSHOT_SOURCES);
    }
    public function educationDetails() { return $this->hasMany(StudentEducationDetail::class); }

    // ── Subject Relations ────────────────────────────────────────────────
    public function studentSubjects()
    {
        return $this->hasMany(StudentSubject::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_subjects')
                    ->withPivot(['year_number', 'subject_role', 'is_auto_included', 'academic_session_id'])
                    ->withTimestamps();
    }

    public function subjectsForSession(int $sessionId)
    {
        return $this->belongsToMany(Subject::class, 'student_subjects')
                    ->wherePivot('academic_session_id', $sessionId)
                    ->withPivot(['year_number', 'subject_role', 'is_auto_included'])
                    ->withTimestamps();
    }

    public function wallets()
    {
        return $this->hasMany(\App\Models\StudentWallet::class);
    }

    public function transportAllocations()
    {
        return $this->hasMany(\App\Models\TransportAllocation::class);
    }

    public function activeTransportAllocation()
    {
        return $this->hasOne(\App\Models\TransportAllocation::class)
            ->where('is_active', true)
            ->latest('id');
    }

    public function transportPayments()
    {
        return $this->hasMany(\App\Models\TransportPayment::class);
    }

    public function libraryMember()
    {
        return $this->hasOne(\App\Models\Library\LibraryMember::class);
    }

    public function academicChangeLogs()
    {
        return $this->hasMany(\App\Models\StudentAcademicChangeLog::class)
            ->latest('id');
    }

    public function admissionDocuments()
    {
        return $this->hasMany(\App\Models\AdmissionDocument::class, 'student_id');
    }
}
