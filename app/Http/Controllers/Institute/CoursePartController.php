<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePart;
use Illuminate\Http\Request;

class CoursePartController extends Controller
{
    public function index($courseId)
    {
        $course = Course::where('id', $courseId)
            ->where('institute_id', auth()->user()->institute_id)
            ->firstOrFail();

        $parts = $course->parts()->orderBy('part_number')->get();

        return view('institute.course_parts.index', compact('course', 'parts'));
    }

    public function create($courseId)
    {
        $course = Course::where('id', $courseId)
            ->where('institute_id', auth()->user()->institute_id)
            ->firstOrFail();

        return view('institute.course_parts.create', compact('course'));
    }

    public function store(Request $request, $courseId)
    {
        $course = Course::where('id', $courseId)
            ->where('institute_id', auth()->user()->institute_id)
            ->firstOrFail();

        $request->validate([
            'part_name' => 'required|string|max:255',
        ]);

        $nextPartNumber = $course->parts()->max('part_number') + 1;

        CoursePart::create([
            'course_id' => $course->id,
            'part_number' => $nextPartNumber ?? 1,
            'part_name' => $request->part_name,
            'status' => true,
        ]);

        return redirect()->route('course.parts.index', $course->id)
            ->with('success', 'Course part added successfully');
    }

    public function destroy($courseId, $partId)
    {
        $course = Course::where('id', $courseId)
            ->where('institute_id', auth()->user()->institute_id)
            ->firstOrFail();

        $part = CoursePart::where('id', $partId)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $part->delete();

        return back()->with('success', 'Course part deleted');
    }
}