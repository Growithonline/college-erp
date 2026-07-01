<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAcademicIdentity extends Model
{
    protected $table = 'student_academic_identity';

    protected $fillable = [
        'student_id', 'institute_id', 'course_id',
        'academic_session_id', 'form_no', 'roll_no',
        'source', 'remarks',
        'admission_type', 'transfer_from',
        'gap_years', 'gap_from', 'gap_to', 'gap_reason',
        // Snapshot fields
        'course_stream_id', 'course_part_id', 'semester_at_time',
        'subjects_json', 'sr_no_snapshot', 'enrollment_no_snapshot',
        'roll_no_snapshot', 'admission_source_snapshot',
        'student_uid_snapshot', 'institute_form_no_snapshot',
        'exam_form_no_snapshot', 'uin_no_snapshot',
        'reference_no_snapshot', 'admission_source_id_snapshot',
        'submitted_date_snapshot', 'admission_date_snapshot',
        'student_status_snapshot',
        'profile_snapshot',
    ];

    protected $casts = [
        'gap_from'         => 'date',
        'gap_to'           => 'date',
        'subjects_json'    => 'array',
        'semester_at_time' => 'integer',
        'gap_years'        => 'integer',
        'submitted_date_snapshot' => 'date',
        'admission_date_snapshot' => 'date',
        'profile_snapshot'        => 'array',
    ];

    // ── Source constants ─────────────────────────────────────────────
    const SOURCE_ADMISSION            = 'admission';
    const SOURCE_PROMOTION            = 'promotion';
    const SOURCE_PRE_SEM_PROMOTE      = 'pre_semester_promote';
    const SOURCE_PRE_SESSION_PROMOTE  = 'pre_session_promote';
    const SOURCE_SESSION_PROMOTION    = 'session_promotion';

    // Snapshot sources — sirf history ke liye, identity page pe nahi dikhne chahiye
    const SNAPSHOT_SOURCES = [
        self::SOURCE_PRE_SEM_PROMOTE,
        self::SOURCE_PRE_SESSION_PROMOTE,
    ];

    // ── Scopes ──────────────────────────────────────────────────────
    /** Sirf real identity rows — snapshots exclude */
    public function scopeRealOnly($query)
    {
        return $query->whereNotIn('source', self::SNAPSHOT_SOURCES);
    }

    /** Sirf pre-promotion snapshots */
    public function scopeSnapshotsOnly($query)
    {
        return $query->whereIn('source', self::SNAPSHOT_SOURCES);
    }

    public function isSnapshot(): bool
    {
        return in_array($this->source, self::SNAPSHOT_SOURCES, true);
    }

    // ── Relationships ────────────────────────────────────────────────
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function courseStream()
    {
        return $this->belongsTo(CourseStream::class, 'course_stream_id');
    }

    public function coursePart()
    {
        return $this->belongsTo(CoursePart::class, 'course_part_id');
    }
}
