<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    public const TYPES = [
        'general' => 'General',
        'exam'    => 'Exam',
        'fee'     => 'Fee',
        'holiday' => 'Holiday',
        'urgent'  => 'Urgent',
        'event'   => 'Event',
    ];

    public const VISIBLE_TO = [
        'staff'   => 'Staff',
        'center'  => 'Centers',
        'channel' => 'Channel Partners',
        'students'=> 'Students',
    ];

    protected $fillable = [
        'institute_id',
        'title',
        'body',
        'notice_type',
        'visible_to',
        'target_course_ids',
        'target_semesters',
        'notice_date',
        'expires_at',
        'is_active',
        'is_pinned',
        'attachment',
        'scheduled_at',
        'email_to',
        'posted_by_staff_id',
        'posted_by_user_id',
    ];

    protected $casts = [
        'notice_date'          => 'date',
        'expires_at'           => 'date',
        'scheduled_at'         => 'datetime',
        'is_active'            => 'boolean',
        'is_pinned'            => 'boolean',
        'visible_to'           => 'array',
        'target_course_ids'    => 'array',
        'target_semesters'     => 'array',
    ];

    public function postedByStaff()
    {
        return $this->belongsTo(StaffMember::class, 'posted_by_staff_id');
    }

    public function reads()
    {
        return $this->hasMany(NoticeRead::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString()))
            ->where(fn($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()));
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    // Fetch active notices visible to a given role for an institute. Pass $student to also
    // apply course/semester targeting (only meaningful for role='students') — notices with no
    // targeting set (null) still reach everyone, matching the pre-targeting default behavior.
    public static function forRole(int $instituteId, string $role, ?Student $student = null)
    {
        $query = static::active()
            ->where('institute_id', $instituteId)
            ->whereJsonContains('visible_to', $role);

        if ($student) {
            $query->where(function ($q) use ($student) {
                $q->whereNull('target_course_ids')
                  ->orWhereJsonContains('target_course_ids', (int) $student->stream?->course_id);
            })->where(function ($q) use ($student) {
                $q->whereNull('target_semesters')
                  ->orWhereJsonContains('target_semesters', (int) $student->current_semester);
            });
        }

        return $query
            ->orderByDesc('is_pinned')
            ->orderByDesc('notice_date')
            ->orderByDesc('id');
    }

    // Narrows a Student query to only those matching this notice's course/semester targeting.
    // No-op (all students) when a constraint isn't set — used wherever notice recipients are
    // resolved (SMS job, email dispatch) so targeting stays consistent everywhere.
    public function applyTargetingToStudentQuery($query)
    {
        if (!empty($this->target_course_ids)) {
            $query->whereHas('stream', fn($q) => $q->whereIn('course_id', $this->target_course_ids));
        }
        if (!empty($this->target_semesters)) {
            $query->whereIn('current_semester', $this->target_semesters);
        }
        return $query;
    }

    public function getNoticeTypeLabelAttribute(): string
    {
        return self::TYPES[$this->notice_type] ?? ucfirst($this->notice_type);
    }

    public function isReadBy(string $readerType, int $readerId): bool
    {
        return $this->reads()
            ->where('reader_type', $readerType)
            ->where('reader_id', $readerId)
            ->exists();
    }
}
