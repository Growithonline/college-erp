<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\FeeAssignment;
use App\Models\FeeType;
use Illuminate\Http\Request;

class FeeAssignmentController extends Controller
{
    public function index()
    {
        $instituteId = auth()->user()->institute_id;
        $sessions  = AcademicSession::where('institute_id', $instituteId)->orderByDesc('start_date')->get();
        $courses   = Course::with('streams.yearRules')->where('institute_id', $instituteId)->where('status', true)->get();
        $feeTypes  = FeeType::where('institute_id', $instituteId)->where('is_active', true)->get();
        $assignments = FeeAssignment::with('feeType','stream.course','coursePart','subjectComponent.subject')
            ->where('institute_id', $instituteId)->get();
        return view('institute.master.fee.assignments.index', compact('sessions','courses','feeTypes','assignments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'fee_type_id'         => 'required|exists:fee_types,id',
            'applies_to'          => 'required|in:course,subject',
            'amount'              => 'required|numeric|min:0',
        ]);

        FeeAssignment::updateOrCreate(
            [
                'institute_id'         => auth()->user()->institute_id,
                'fee_type_id'          => $request->fee_type_id,
                'academic_session_id'  => $request->academic_session_id,
                'applies_to'           => $request->applies_to,
                'course_stream_id'     => $request->course_stream_id,
                'course_part_id'       => $request->course_part_id,
                'subject_component_id' => $request->subject_component_id,
            ],
            ['amount' => $request->amount, 'is_active' => true]
        );

        return back()->with('success', 'Fee assignment saved!');
    }

    public function destroy(FeeAssignment $feeAssignment)
    {
        abort_if($feeAssignment->institute_id !== auth()->user()->institute_id, 403);
        $feeAssignment->delete();
        return back()->with('success', 'Assignment removed!');
    }
}
