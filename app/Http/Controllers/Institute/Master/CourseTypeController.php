<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\CourseType;
use Illuminate\Http\Request;

class CourseTypeController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index()
    {
        $courseTypes = CourseType::forInstitute($this->instituteId())
            ->with('courses')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('institute.master.course-types.index', compact('courseTypes'));
    }

    public function store(Request $request)
    {
        $instituteId = $this->instituteId();
        $name = strtoupper(CourseType::normalizeName($request->input('name', '')));

        $request->validate(['name' => 'required|string|max:100']);

        if (CourseType::forInstitute($instituteId)->where('name', $name)->exists()) {
            return back()->withErrors(['name' => '"' . $name . '" already exists.'])->withInput();
        }

        $maxSort = CourseType::forInstitute($instituteId)->max('sort_order') ?? 0;

        CourseType::create([
            'institute_id'    => $instituteId,
            'name'            => $name,
            'sort_order'      => $maxSort + 1,
            'is_active'       => true,
            'education_level' => $request->input('education_level') ?: null,
        ]);

        return back()->with('success', 'Course type "' . $name . '" added!');
    }

    public function update(Request $request, CourseType $courseType)
    {
        abort_if($courseType->institute_id !== $this->instituteId(), 403);

        $name = strtoupper(CourseType::normalizeName($request->input('name', '')));
        $request->validate(['name' => 'required|string|max:100']);

        if (CourseType::forInstitute($this->instituteId())
            ->where('name', $name)
            ->where('id', '!=', $courseType->id)
            ->exists()) {
            return back()->withErrors(['name_' . $courseType->id => '"' . $name . '" already exists.'])->withInput();
        }

        $courseType->update([
            'name'            => $name,
            'education_level' => $request->input('education_level') ?: null,
        ]);
        return back()->with('success', 'Course type updated!');
    }

    public function destroy(CourseType $courseType)
    {
        abort_if($courseType->institute_id !== $this->instituteId(), 403);

        if ($courseType->courses()->exists()) {
            return back()->withErrors(['delete' => '"' . $courseType->name . '" has linked courses. Reassign them before deleting.']);
        }

        $courseType->delete();
        return back()->with('success', '"' . $courseType->name . '" deleted!');
    }

    public function toggle(CourseType $courseType)
    {
        abort_if($courseType->institute_id !== $this->instituteId(), 403);
        $courseType->update(['is_active' => !$courseType->is_active]);
        return back()->with('success', 'Status updated!');
    }

    // ── Static helper ────────────────────────────────────────────────────
    public static function getActiveTypes(int $instituteId): \Illuminate\Support\Collection
    {
        return CourseType::forInstitute($instituteId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'education_level']);
    }
}
