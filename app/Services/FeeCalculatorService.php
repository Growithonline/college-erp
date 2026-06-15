<?php

namespace App\Services;

use App\Models\CourseFeeRule;
use App\Models\FeeAssignment;
use App\Models\Student;
use App\Models\SubjectFeeRule;
use App\Models\Subject;
use App\Models\SubjectComponent;
use Illuminate\Support\Collection;

class FeeCalculatorService
{
    // ════════════════════════════════════════════════════════════════════
    //  MAIN — Student ka poora fee calculate karo
    //  Returns: [ 'items' => [...], 'total' => 0.00 ]
    // ════════════════════════════════════════════════════════════════════
    public static function calculate(
        int    $instituteId,
        int    $sessionId,
        int    $courseId,
        int    $coursePart,       // 1, 2, 3
        int    $semester,         // 1 or 2
        string $studentType,      // regular / ex_student / lateral
        string $admissionSource,  // direct / center / channel_partner
        string $category,         // general / obc / sc / st
        string $gender,           // male / female / other
        array  $subjectIds = [],  // enrolled subject IDs
        ?int   $courseStreamId = null,
        ?int   $coursePartId = null,
        bool   $includeYearlyFees = true  // false = semester promotion ke liye (yearly fees already charged in sem 1)
    ): array {
        $items = collect();

        // ── 1. Course Fees (type-wise) ───────────────────────────────────
        $courseRules = self::getCourseFeeRules(
            $instituteId, $sessionId, $courseId, $coursePart, $semester,
            $studentType, $admissionSource, $category, $gender, $includeYearlyFees
        );

        foreach ($courseRules as $rule) {
            $items->push([
                'type'        => 'course',
                'fee_type_id' => $rule->fee_type_id,
                'label'       => $rule->feeType->name ?? 'Course Fee',
                'subject_id'  => null,
                'amount'      => (float) $rule->amount,
                'note'        => $rule->feeType->name ?? '',
            ]);
        }

        $courseAssignments = self::getCourseAssignments(
            instituteId: $instituteId,
            sessionId: $sessionId,
            courseStreamId: $courseStreamId,
            coursePartId: $coursePartId
        );

        foreach ($courseAssignments as $assignment) {
            if ($courseRules->contains('fee_type_id', $assignment->fee_type_id)) {
                continue;
            }

            $items->push([
                'type'        => 'course_assignment',
                'fee_type_id' => $assignment->fee_type_id,
                'label'       => $assignment->feeType->name ?? 'Course Fee',
                'subject_id'  => null,
                'amount'      => (float) $assignment->amount,
                'note'        => $assignment->stream?->name ?? '',
            ]);
        }

        // ── 2. Subject Fees + Practical Fees ────────────────────────────
        if (!empty($subjectIds)) {
            $subjectRules = SubjectFeeRule::with(['subject'])
                ->where('institute_id', $instituteId)
                ->where('academic_session_id', $sessionId)
                ->where('course_id', $courseId)
                ->where('course_part', $coursePart)
                ->where(function ($q) use ($semester, $includeYearlyFees) {
                    if ($includeYearlyFees) {
                        $q->where('semester', $semester)->orWhere('semester', 0);
                    } else {
                        $q->where('semester', $semester);
                    }
                })
                ->whereIn('subject_id', $subjectIds)
                ->where('is_active', true)
                ->get();

            // Load subjects to check has_practical
            $subjects = Subject::whereIn('id', $subjectIds)
                ->pluck('has_practical', 'id');

            foreach ($subjectRules as $rule) {
                // Subject Fee
                if ($rule->subject_fee > 0) {
                    $items->push([
                        'type'        => 'subject',
                        'fee_type_id' => null,
                        'label'       => ($rule->subject->name ?? 'Subject') . ' — Subject Fee',
                        'subject_id'  => $rule->subject_id,
                        'amount'      => (float) $rule->subject_fee,
                        'note'        => 'Sem ' . $semester,
                    ]);
                }

                // Practical Fee (only if subject has practical)
                if ($rule->practical_fee > 0 && ($subjects[$rule->subject_id] ?? false)) {
                    $items->push([
                        'type'        => 'practical',
                        'fee_type_id' => null,
                        'label'       => ($rule->subject->name ?? 'Subject') . ' — Practical Fee',
                        'subject_id'  => $rule->subject_id,
                        'amount'      => (float) $rule->practical_fee,
                        'note'        => 'Sem ' . $semester,
                    ]);
                }
            }

            $subjectAssignments = self::getSubjectComponentAssignments(
                instituteId: $instituteId,
                sessionId: $sessionId,
                subjectIds: $subjectIds,
                courseStreamId: $courseStreamId,
                coursePartId: $coursePartId
            );

            foreach ($subjectAssignments as $assignment) {
                $subjectName = $assignment->subjectComponent?->subject?->name ?? 'Subject';
                $feeTypeName = $assignment->feeType->name ?? 'Fee';

                $items->push([
                    'type'        => 'subject_assignment',
                    'fee_type_id' => $assignment->fee_type_id,
                    'label'       => $subjectName . ' — ' . $feeTypeName,
                    'subject_id'  => $assignment->subjectComponent?->subject_id,
                    'amount'      => (float) $assignment->amount,
                    'note'        => ucfirst($assignment->subjectComponent?->component_type ?? 'theory'),
                ]);
            }
        }

        // ── 3. Misc Fees (Exam, Admit Card etc.) ────────────────────────
        // Existing fee_assignments table se (applies_to = 'course', stream = null)
        $miscFees = FeeAssignment::with('feeType')
            ->where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)
            ->where('is_active', true)
            ->where('applies_to', 'course')
            ->whereNull('course_stream_id') // global fees
            ->get();

        foreach ($miscFees as $fee) {
            // Skip if already covered by course_fee_rules or course_assignments (prevents duplicate when a
            // stream-specific assignment and a global null-stream assignment both exist for the same fee type)
            $alreadyCovered = $courseRules->contains('fee_type_id', $fee->fee_type_id)
                || $courseAssignments->contains('fee_type_id', $fee->fee_type_id);
            if (!$alreadyCovered) {
                $items->push([
                    'type'        => 'misc',
                    'fee_type_id' => $fee->fee_type_id,
                    'label'       => $fee->feeType->name ?? 'Fee',
                    'subject_id'  => null,
                    'amount'      => (float) $fee->amount,
                    'note'        => '',
                ]);
            }
        }

        $total = $items->sum('amount');

        return [
            'items'   => $items->values()->toArray(),
            'total'   => $total,
            'summary' => [
                'course_fee'    => $items->whereIn('type', ['course', 'course_assignment'])->sum('amount'),
                'subject_fee'   => $items->whereIn('type', ['subject', 'subject_assignment'])->sum('amount'),
                'practical_fee' => $items->where('type', 'practical')->sum('amount'),
                'misc_fee'      => $items->where('type', 'misc')->sum('amount'),
            ],
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    //  MATCH COURSE FEE RULES — Most specific rule wins
    //  Priority: specific > 'all'
    //  e.g. Regular+Center > Regular+all > all+Center > all+all
    // ════════════════════════════════════════════════════════════════════
    private static function getCourseFeeRules(
        int $instituteId, int $sessionId, int $courseId,
        int $coursePart, int $semester,
        string $studentType, string $admissionSource,
        string $category, string $gender,
        bool $includeYearlyFees = true
    ): Collection {
        // Fetch all matching rules (specific + 'all' fallbacks)
        $rules = CourseFeeRule::with('feeType')
            ->where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)
            ->where('course_id', $courseId)
            ->where('is_active', true)
            ->where(fn($q) => $q->where('course_part', $coursePart)->orWhere('course_part', 0))
            ->where(function ($q) use ($semester, $includeYearlyFees) {
                // semester=0 rules tab hi include karo jab yearly fees chahiye
                // (admission ya session promotion pe); semester promotion pe skip
                if ($includeYearlyFees) {
                    $q->where('semester', $semester)->orWhere('semester', 0);
                } else {
                    $q->where('semester', $semester);
                }
            })
            ->where(fn($q) => $q->whereIn('student_type', [$studentType, 'all']))
            ->where(fn($q) => $q->whereIn('admission_source', [$admissionSource, 'all']))
            ->where(fn($q) => $q->whereIn('category', [$category, 'all']))
            ->where(fn($q) => $q->whereIn('gender', [$gender, 'all']))
            ->get();

        // Group by fee_type_id — most specific rule jeetega
        // Specificity score: specific field = 1 point, 'all' = 0 points
        return $rules->groupBy('fee_type_id')->map(function ($group) use (
            $studentType, $admissionSource, $category, $gender
        ) {
            return $group->sortByDesc(function ($rule) use (
                $studentType, $admissionSource, $category, $gender
            ) {
                $score = 0;
                if ($rule->student_type    !== 'all') $score++;
                if ($rule->admission_source !== 'all') $score++;
                if ($rule->category        !== 'all') $score++;
                if ($rule->gender          !== 'all') $score++;
                return $score;
            })->first(); // Highest specificity rule
        })->values();
    }

    // ════════════════════════════════════════════════════════════════════
    //  HELPER — Student object se calculate karo directly
    // ════════════════════════════════════════════════════════════════════
    public static function calculateForStudent(
        Student $student,
        int $coursePart,
        int $semester,
        array $subjectIds = []
    ): array {
        return self::calculate(
            instituteId:     $student->institute_id,
            sessionId:       $student->academic_session_id,
            courseId:        $student->stream->course_id ?? 0,
            coursePart:      $coursePart,
            semester:        $semester,
            studentType:     $student->student_type ?? 'regular',
            admissionSource: $student->admission_source ?? 'direct',
            category:        $student->category ?? 'general',
            gender:          $student->gender ?? 'male',
            subjectIds:      $subjectIds,
            courseStreamId:  $student->course_stream_id,
            coursePartId:    $student->course_part_id,
        );
    }

    private static function getCourseAssignments(
        int $instituteId,
        int $sessionId,
        ?int $courseStreamId,
        ?int $coursePartId
    ): Collection {
        return FeeAssignment::with(['feeType', 'stream'])
            ->where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)
            ->where('applies_to', 'course')
            ->where('is_active', true)
            ->where(function ($q) use ($courseStreamId) {
                $q->whereNull('course_stream_id');
                if ($courseStreamId) {
                    $q->orWhere('course_stream_id', $courseStreamId);
                }
            })
            ->where(function ($q) use ($coursePartId) {
                $q->whereNull('course_part_id');
                if ($coursePartId) {
                    $q->orWhere('course_part_id', $coursePartId);
                }
            })
            ->orderByRaw('case when course_stream_id is null then 0 else 1 end desc')
            ->orderByRaw('case when course_part_id is null then 0 else 1 end desc')
            ->get()
            ->groupBy('fee_type_id')
            ->map(fn($group) => $group->first())
            ->values();
    }

    private static function getSubjectComponentAssignments(
        int $instituteId,
        int $sessionId,
        array $subjectIds,
        ?int $courseStreamId,
        ?int $coursePartId
    ): Collection {
        $componentIds = SubjectComponent::whereIn('subject_id', $subjectIds)
            ->where('is_active', true)
            ->pluck('id');

        if ($componentIds->isEmpty()) {
            return collect();
        }

        return FeeAssignment::with(['feeType', 'subjectComponent.subject'])
            ->where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)
            ->where('applies_to', 'subject')
            ->where('is_active', true)
            ->whereIn('subject_component_id', $componentIds)
            ->where(function ($q) use ($courseStreamId) {
                $q->whereNull('course_stream_id');
                if ($courseStreamId) {
                    $q->orWhere('course_stream_id', $courseStreamId);
                }
            })
            ->where(function ($q) use ($coursePartId) {
                $q->whereNull('course_part_id');
                if ($coursePartId) {
                    $q->orWhere('course_part_id', $coursePartId);
                }
            })
            ->orderByRaw('case when course_stream_id is null then 0 else 1 end desc')
            ->orderByRaw('case when course_part_id is null then 0 else 1 end desc')
            ->get()
            ->groupBy(function ($assignment) {
                return $assignment->fee_type_id . ':' . $assignment->subject_component_id;
            })
            ->map(fn($group) => $group->first())
            ->values();
    }
}
