<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\FeePlan;
use App\Models\FeePlanInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FeePlanController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();

        $courses = Course::where('institute_id', $instituteId)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $plans = FeePlan::with(['installments', 'course'])
            ->where('institute_id', $instituteId)
            ->when($request->course_id, fn($q) => $q->where(function ($q2) use ($request) {
                $q2->where('course_id', $request->course_id)->orWhereNull('course_id');
            }))
            ->orderBy('name')
            ->get();

        return view('institute.master.fee-plans.index', compact('plans', 'courses'));
    }

    public function store(Request $request)
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'course_id'         => ['nullable', Rule::exists('courses', 'id')->where('institute_id', $instituteId)],
            'installment_count' => 'required|integer|min:1|max:12',
            'description'       => 'nullable|string|max:500',
            'installments'      => 'required|array|min:1|max:12',
            'installments.*.label'             => 'required|string|max:100',
            'installments.*.percentage'        => 'required|numeric|min:0.01|max:100',
            'installments.*.due_trigger'       => ['required', Rule::in(['at_admission', 'semester_start', 'months_after'])],
            'installments.*.due_semester'      => 'nullable|integer|min:1|max:12',
            'installments.*.due_months_after'  => 'nullable|integer|min:1|max:60',
        ]);

        $totalPct = collect($validated['installments'])->sum('percentage');
        if (abs($totalPct - 100) > 0.01) {
            return back()->withErrors(['installments' => 'Installment percentages must add up to 100%. Current total: ' . $totalPct . '%'])->withInput();
        }

        DB::transaction(function () use ($validated, $instituteId) {
            $plan = FeePlan::create([
                'institute_id'      => $instituteId,
                'course_id'         => $validated['course_id'] ?? null,
                'name'              => $validated['name'],
                'installment_count' => count($validated['installments']),
                'description'       => $validated['description'] ?? null,
                'is_active'         => true,
            ]);

            foreach ($validated['installments'] as $i => $inst) {
                FeePlanInstallment::create([
                    'fee_plan_id'        => $plan->id,
                    'installment_number' => $i + 1,
                    'label'              => $inst['label'],
                    'percentage'         => $inst['percentage'],
                    'due_trigger'        => $inst['due_trigger'],
                    'due_semester'       => $inst['due_semester'] ?? null,
                    'due_months_after'   => $inst['due_months_after'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Fee plan "' . $validated['name'] . '" created successfully.');
    }

    public function update(Request $request, FeePlan $feePlan)
    {
        $instituteId = $this->instituteId();

        if ($feePlan->institute_id !== $instituteId) {
            abort(403);
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'boolean',
        ]);

        $feePlan->update($validated);

        return back()->with('success', 'Fee plan updated.');
    }

    public function toggleStatus(FeePlan $feePlan)
    {
        if ($feePlan->institute_id !== $this->instituteId()) {
            abort(403);
        }

        $feePlan->update(['is_active' => !$feePlan->is_active]);

        return back()->with('success', $feePlan->is_active ? 'Fee plan activated.' : 'Fee plan deactivated.');
    }

    public function destroy(FeePlan $feePlan)
    {
        $instituteId = $this->instituteId();

        if ($feePlan->institute_id !== $instituteId) {
            abort(403);
        }

        if ($feePlan->students()->exists()) {
            return back()->withErrors(['delete' => 'Cannot delete — students are assigned to this plan.']);
        }

        DB::transaction(function () use ($feePlan) {
            $feePlan->installments()->delete();
            $feePlan->delete();
        });

        return back()->with('success', 'Fee plan deleted.');
    }

    public function report(Request $request)
    {
        $instituteId = $this->instituteId();
        $courseId    = $request->input('course_id');

        $plans = FeePlan::with(['installments', 'course'])
            ->withCount(['students as student_count' => function ($q) use ($instituteId) {
                $q->where('institute_id', $instituteId);
            }])
            ->where('institute_id', $instituteId)
            ->when($courseId, fn($q) => $q->where(function ($q2) use ($courseId) {
                $q2->where('course_id', $courseId)->orWhereNull('course_id');
            }))
            ->orderBy('name')
            ->get();

        $courses = Course::where('institute_id', $instituteId)
            ->where('status', true)->orderBy('name')->get();

        return view('institute.master.fee-plans.report', compact('plans', 'courses', 'courseId'));
    }

    // AJAX: get active fee plans for a course (used in admission form)
    public function forCourse(Request $request)
    {
        $instituteId = $this->instituteId();
        $courseId    = (int) $request->course_id;

        $plans = FeePlan::with('installments')
            ->where('institute_id', $instituteId)
            ->where('is_active', true)
            ->where(function ($q) use ($courseId) {
                $q->whereNull('course_id')->orWhere('course_id', $courseId);
            })
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id'                 => $p->id,
                'name'               => $p->name,
                'installment_count'  => $p->installment_count,
                'installments'       => $p->installments->map(fn($i) => [
                    'number'      => $i->installment_number,
                    'label'       => $i->label,
                    'percentage'  => (float) $i->percentage,
                    'due_trigger' => $i->due_trigger,
                ]),
            ]);

        return response()->json($plans);
    }
}
