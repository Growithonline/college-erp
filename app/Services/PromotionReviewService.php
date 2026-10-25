<?php

namespace App\Services;

use App\Models\Student;

// The pre-promotion check. A student who has paid MORE than a fee item now costs (excess) while
// another fee is still pending must have that excess set against the pending fee before the
// promotion goes through — otherwise the promotion would carry the full due forward while the
// excess became advance, and the two would never meet. Promotion (semester and session, single
// and bulk) refuses such students and sends the user to the review page, which adjusts them in
// one click.
class PromotionReviewService
{
    /**
     * Active students of the institute that still have excess which could be set against a pending fee.
     *
     * @param  array<int, int|string>  $studentIds
     * @return array<int, array{student: Student, plan: array}>
     */
    public static function blockers(array $studentIds, int $instituteId): array
    {
        if (empty($studentIds)) {
            return [];
        }

        $out = [];

        Student::where('institute_id', $instituteId)
            ->where('status', 'active')
            ->whereIn('id', $studentIds)
            ->orderBy('name')
            ->get()
            ->each(function (Student $student) use (&$out) {
                $plan = ExcessAdjustmentService::plan($student, (int) $student->academic_session_id);

                if (!empty($plan['moves'])) {
                    $out[] = ['student' => $student, 'plan' => $plan];
                }
            });

        return $out;
    }
}
