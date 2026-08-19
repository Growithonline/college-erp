<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseStream;
use App\Models\SmsBroadcast;
use App\Models\StaffMember;
use App\Models\StaffRole;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

// Single source of truth for "who does this SMS broadcast reach" — used both by the live
// recipient-count preview while composing (raw, not-yet-saved filters) and by the send job
// once a broadcast is persisted (SmsBroadcast::resolveRecipientQuery). Keeping both paths on
// one resolver means the count an admin sees before sending always matches who actually gets it.
class SmsBroadcastTargeting
{
    // Re-validates posted ids against this institute's own data — never trust them directly.
    public static function sanitizeFilters(int $instituteId, string $audienceType, array $filters): array
    {
        $courseIds    = [];
        $streamIds    = [];
        $semesters    = [];
        $staffRoleIds = [];

        if ($audienceType === SmsBroadcast::AUDIENCE_STUDENT) {
            if (!empty($filters['target_course_ids'])) {
                $courseIds = Course::where('institute_id', $instituteId)
                    ->whereIn('id', $filters['target_course_ids'])->pluck('id')->all();
            }
            if (!empty($filters['target_stream_ids'])) {
                $streamIds = CourseStream::whereHas('course', fn ($q) => $q->where('institute_id', $instituteId))
                    ->whereIn('id', $filters['target_stream_ids'])->pluck('id')->all();
            }
            if (!empty($filters['target_semesters'])) {
                $semesters = array_values(array_unique(array_map('intval', $filters['target_semesters'])));
            }
        } elseif (!empty($filters['target_staff_role_ids'])) {
            $staffRoleIds = StaffRole::where('institute_id', $instituteId)
                ->whereIn('id', $filters['target_staff_role_ids'])->pluck('id')->all();
        }

        return [
            'target_course_ids'     => $courseIds ?: null,
            'target_stream_ids'     => $streamIds ?: null,
            'target_semesters'      => $semesters ?: null,
            'target_staff_role_ids' => $staffRoleIds ?: null,
        ];
    }

    // Base recipient pool for an audience — institute-scoped, active, has a mobile number.
    public static function baseQuery(int $instituteId, string $audienceType): Builder
    {
        if ($audienceType === SmsBroadcast::AUDIENCE_STAFF) {
            return StaffMember::where('institute_id', $instituteId)
                ->where('status', true)
                ->whereNotNull('mobile');
        }

        return Student::where('institute_id', $instituteId)
            ->where('status', 'active')
            ->whereNotNull('mobile');
    }

    // Narrows a base query with already-sanitized targeting filters + optional specific-id override.
    public static function applyFilters(Builder $query, string $audienceType, array $filters): Builder
    {
        if ($audienceType === SmsBroadcast::AUDIENCE_STUDENT) {
            if (!empty($filters['target_stream_ids'])) {
                $query->whereIn('course_stream_id', $filters['target_stream_ids']);
            } elseif (!empty($filters['target_course_ids'])) {
                $query->whereHas('stream', fn ($q) => $q->whereIn('course_id', $filters['target_course_ids']));
            }
            if (!empty($filters['target_semesters'])) {
                $query->whereIn('current_semester', $filters['target_semesters']);
            }
        } elseif (!empty($filters['target_staff_role_ids'])) {
            $query->whereIn('staff_role_id', $filters['target_staff_role_ids']);
        }

        if (($filters['recipient_mode'] ?? null) === SmsBroadcast::RECIPIENT_MODE_SPECIFIC
            && !empty($filters['specific_recipient_ids'])) {
            $query->whereIn('id', $filters['specific_recipient_ids']);
        }

        return $query;
    }

    // Sanitize + build + filter in one call — ready for ->count(), ->pluck('mobile'), etc.
    public static function resolve(int $instituteId, string $audienceType, array $filters): Builder
    {
        $sanitized = self::sanitizeFilters($instituteId, $audienceType, $filters);
        $query     = self::baseQuery($instituteId, $audienceType);

        return self::applyFilters($query, $audienceType, array_merge($filters, $sanitized));
    }

    // Variable names filled per-recipient at send time from their own record — e.g. {name} must
    // be each student's own name, never one value typed once and broadcast to everyone. Anything
    // in a template's variable list that ISN'T one of these is a shared value the admin types
    // once on the compose page (exam date, venue, promo text, ...).
    public static function recipientAutoVarNames(string $audienceType): array
    {
        return $audienceType === SmsBroadcast::AUDIENCE_STAFF
            ? ['name', 'mobile', 'role', 'institute_name']
            : ['name', 'mobile', 'course', 'roll_no', 'student_uid', 'institute_name'];
    }

    // Auto-fill vars an admin can still override with one fixed value for the whole broadcast —
    // most messages "from the college" want the college's own office number in {mobile}, not
    // each recipient's own number. Blank on the compose page = fall back to the recipient's own.
    public static function overridableAutoVarNames(): array
    {
        return ['mobile'];
    }

    // The actual per-recipient values for the reserved names above — used by the send job to
    // merge over the admin's shared template_values just before sending each message.
    public static function buildRecipientVars($recipient, string $audienceType): array
    {
        $instituteName = $recipient->institute?->name ?? '';

        if ($audienceType === SmsBroadcast::AUDIENCE_STAFF) {
            return [
                'name'           => $recipient->name,
                'mobile'         => $recipient->mobile,
                'role'           => $recipient->role?->name ?? '',
                'institute_name' => $instituteName,
            ];
        }

        return [
            'name'           => $recipient->name,
            'mobile'         => $recipient->mobile,
            'course'         => $recipient->stream?->course?->name ?? '',
            'roll_no'        => $recipient->roll_no ?? '',
            'student_uid'    => $recipient->student_uid ?? '',
            'institute_name' => $instituteName,
        ];
    }
}
