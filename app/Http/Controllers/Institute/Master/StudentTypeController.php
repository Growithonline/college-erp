<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\StudentType;
use Illuminate\Http\Request;

class StudentTypeController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index()
    {
        $studentTypes = StudentType::forInstitute($this->instituteId())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('institute.master.student-types.index', compact('studentTypes'));
    }

    public function store(Request $request)
    {
        $instituteId = $this->instituteId();

        $name = strtoupper(StudentType::normalizeName($request->input('name', '')));
        $slug = StudentType::makeSlug($name);

        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        if (StudentType::forInstitute($instituteId)->where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => '"' . $name . '" already exists.'])->withInput();
        }

        $maxSort = StudentType::forInstitute($instituteId)->max('sort_order') ?? 0;

        StudentType::create([
            'institute_id' => $instituteId,
            'name'         => $name,
            'slug'         => $slug,
            'sort_order'   => $maxSort + 1,
            'is_active'    => true,
        ]);

        return back()->with('success', 'Student type "' . $name . '" added!');
    }

    public function update(Request $request, StudentType $studentType)
    {
        abort_if($studentType->institute_id !== $this->instituteId(), 403);

        $name = strtoupper(StudentType::normalizeName($request->input('name', '')));
        $slug = StudentType::makeSlug($name);

        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        if (StudentType::forInstitute($this->instituteId())
            ->where('slug', $slug)
            ->where('id', '!=', $studentType->id)
            ->exists()) {
            return back()->withErrors(['name_' . $studentType->id => '"' . $name . '" already exists.'])->withInput();
        }

        $studentType->update([
            'name' => $name,
            'slug' => $slug,
        ]);

        return back()->with('success', 'Student type updated!');
    }

    public function destroy(StudentType $studentType)
    {
        abort_if($studentType->institute_id !== $this->instituteId(), 403);
        $studentType->delete();
        return back()->with('success', '"' . $studentType->name . '" deleted!');
    }

    public function toggle(StudentType $studentType)
    {
        abort_if($studentType->institute_id !== $this->instituteId(), 403);
        $studentType->update(['is_active' => !$studentType->is_active]);
        return back()->with('success', 'Status updated!');
    }

    public function reorder(Request $request)
    {
        $instituteId = $this->instituteId();
        foreach ($request->input('order', []) as $i => $id) {
            StudentType::forInstitute($instituteId)->where('id', $id)
                ->update(['sort_order' => $i + 1]);
        }
        return response()->json(['ok' => true]);
    }

    // ── Static helper: institute ke liye active student types ─────────────
    public static function getActiveTypes(int $instituteId): \Illuminate\Support\Collection
    {
        return StudentType::forInstitute($instituteId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }
}
