<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::where('institute_id', auth()->user()->institute_id)
            ->latest()
            ->get();

        return view('institute.courses.index', compact('courses'));
    }

    public function create()
    {
        $courseTypes = \App\Models\CourseType::forInstitute(auth()->user()->institute_id)->active()->orderBy('sort_order')->orderBy('name')->get();
        return view('institute.courses.create', compact('courseTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_type_id' => ['required', \Illuminate\Validation\Rule::exists('course_types', 'id')->where('institute_id', auth()->user()->institute_id)],
            'name' => 'required',
            'code' => 'required',
            'duration' => 'required|integer|min:1',
            'duration_type' => 'required|in:year,month',
            'structure_type' => 'required|in:semester,yearly,modular',
        ]);

        Course::create([
            'institute_id' => auth()->user()->institute_id,
            'course_type_id' => $request->course_type_id,
            'name' => $request->name,
            'code' => $request->code,
            'duration' => $request->duration,
            'duration_type' => $request->duration_type,
            'structure_type' => $request->structure_type,
            'lateral_entry_allowed' => $request->has('lateral_entry_allowed'),
            'lateral_entry_start_part' => $request->lateral_entry_start_part,
            'status' => true,
        ]);

        return redirect()->route('courses.index')
            ->with('success', 'Course created successfully');
    }
}