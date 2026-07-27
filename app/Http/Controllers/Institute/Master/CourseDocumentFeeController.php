<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseDocumentFee;
use Illuminate\Http\Request;

class CourseDocumentFeeController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function edit()
    {
        $instituteId = $this->instituteId();

        $courses = Course::where('institute_id', $instituteId)
            ->with('documentFee')
            ->orderBy('name')
            ->get();

        return view('institute.master.document-batches.fee-settings', compact('courses'));
    }

    public function update(Request $request)
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'fees'                    => 'array',
            'fees.*.marksheet_fee'    => 'nullable|numeric|min:0',
            'fees.*.degree_fee'       => 'nullable|numeric|min:0',
        ]);

        $courseIds = Course::where('institute_id', $instituteId)->pluck('id');

        foreach ($validated['fees'] ?? [] as $courseId => $row) {
            if (!$courseIds->contains((int) $courseId)) {
                continue;
            }

            CourseDocumentFee::updateOrCreate(
                ['institute_id' => $instituteId, 'course_id' => $courseId],
                [
                    'marksheet_fee' => $row['marksheet_fee'] ?? null,
                    'degree_fee'    => $row['degree_fee'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Fee settings save ho gaye.');
    }
}
