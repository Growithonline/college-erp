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
        'notice_date',
        'expires_at',
        'is_active',
        'is_pinned',
        'attachment',
        'scheduled_at',
        'email_to',
        'sms_to',
        'posted_by_staff_id',
        'posted_by_user_id',
    ];

    protected $casts = [
        'notice_date'  => 'date',
        'expires_at'   => 'date',
        'scheduled_at' => 'datetime',
        'is_active'    => 'boolean',
        'is_pinned'    => 'boolean',
        'visible_to'   => 'array',
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

    // Fetch active notices visible to a given role for an institute
    public static function forRole(int $instituteId, string $role)
    {
        return static::active()
            ->where('institute_id', $instituteId)
            ->whereJsonContains('visible_to', $role)
            ->orderByDesc('is_pinned')
            ->orderByDesc('notice_date')
            ->orderByDesc('id');
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
