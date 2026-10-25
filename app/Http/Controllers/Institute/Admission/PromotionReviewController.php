<?php

namespace App\Http\Controllers\Institute\Admission;

use App\Http\Controllers\Controller;
use App\Services\ExcessAdjustmentService;
use App\Services\PromotionReviewService;
use Illuminate\Http\Request;

// Review step in front of every promotion (see PromotionReviewService): lists the students whose
// excess has not been set against their pending fees yet and adjusts them all in one click.
// Promotion itself refuses these students until it is done.
class PromotionReviewController extends Controller
{
    private function authorizeAccess(): int
    {
        $staff = auth()->guard('staff')->user();
        abort_if($staff && !$staff->hasPermission('student_promote'), 403, 'You are not allowed to promote students.');

        $user = $staff ?: auth()->user();

        return (int) ($user?->institute_id ?? abort(403));
    }

    /** @return array<int, int> */
    private function pendingIds(): array
    {
        return array_values(array_unique(array_map('intval', (array) session('promotion_review_ids', []))));
    }

    public function review()
    {
        $instituteId = $this->authorizeAccess();
        $rows = PromotionReviewService::blockers($this->pendingIds(), $instituteId);

        $staff = auth()->guard('staff')->user();
        if ($staff) {
            $rows = array_values(array_filter($rows, fn($r) => $staff->canAccessStudentForOperations($r['student'])));
        }

        return view('institute.admission.promotions.review', [
            'rows'    => $rows,
            'backUrl' => session('promotion_review_back'),
        ]);
    }

    public function adjust(Request $request)
    {
        $instituteId = $this->authorizeAccess();
        $staff = auth()->guard('staff')->user();
        $actor = ['type' => $staff ? 'staff' : 'admin', 'id' => ($staff ?: auth()->user())?->id];

        $done = 0;
        $amount = 0.0;

        foreach (PromotionReviewService::blockers($this->pendingIds(), $instituteId) as $row) {
            if ($staff && !$staff->canAccessStudentForOperations($row['student'])) {
                continue;
            }

            $moves = ExcessAdjustmentService::apply($row['student'], (int) $row['student']->academic_session_id, $actor);
            if (!empty($moves)) {
                $done++;
                $amount += array_sum(array_column($moves, 'amount'));
            }
        }

        return redirect()->back()->with('success', "Adjusted {$done} student(s), ₹" . number_format($amount, 2)
            . ' of excess set against pending fees. You can go back and promote now.');
    }
}
