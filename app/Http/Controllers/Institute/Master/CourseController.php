<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePart;
use App\Models\CourseType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::with('type', 'streams')
            ->where('institute_id', auth()->user()->institute_id)
            ->orderBy('name')->get();
        return view('institute.master.courses.index', compact('courses'));
    }

    public function create()
    {
        $courseTypes = CourseType::forInstitute(auth()->user()->institute_id)->active()->orderBy('sort_order')->orderBy('name')->get();
        return view('institute.master.courses.create', compact('courseTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_type_id'   => ['required', Rule::exists('course_types', 'id')->where('institute_id', auth()->user()->institute_id)],
            'name'             => 'required|string|max:100',
            'code'             => 'required|string|max:20',
            'duration'         => 'required|integer|min:1',
            'duration_type'    => 'required|in:year,month',
            'structure_type'   => 'required|in:semester,yearly,modular',
            'max_atkt_allowed' => 'required|integer|min:0|max:10',
        ]);

        $instituteId = auth()->user()->institute_id;

        if (Course::where('institute_id', $instituteId)->where('code', strtoupper($request->code))->exists()) {
            return back()->withInput()->withErrors(['code' => 'This course code already exists.']);
        }

        DB::transaction(function () use ($request, $instituteId) {

            $course = Course::create([
                'institute_id'             => $instituteId,
                'course_type_id'           => $request->course_type_id,
                'name'                     => strtoupper($request->name),
                'code'                     => strtoupper($request->code),
                'duration'                 => $request->duration,
                'duration_type'            => $request->duration_type,
                'structure_type'           => $request->structure_type,
                'max_atkt_allowed'         => $request->max_atkt_allowed,
                'lateral_entry_allowed'    => $request->boolean('lateral_entry_allowed'),
                'lateral_entry_start_part' => $request->lateral_entry_start_part,
                'status'                   => true,
            ]);

            // Auto-generate CourseParts based on duration + structure_type
            self::generateCourseParts($course);
        });

        return redirect()->route('master.courses.index')
            ->with('success', "Course '{$request->name}' created successfully!");
    }

    public function edit(Course $course)
    {
        $this->authorizeCourse($course);
        $courseTypes = CourseType::forInstitute(auth()->user()->institute_id)->active()->orderBy('sort_order')->orderBy('name')->get();
        return view('institute.master.courses.edit', compact('course', 'courseTypes'));
    }

    public function update(Request $request, Course $course)
    {
        $this->authorizeCourse($course);
        $request->validate([
            'course_type_id'   => ['required', Rule::exists('course_types', 'id')->where('institute_id', auth()->user()->institute_id)],
            'name'             => 'required|string|max:100',
            'code'             => 'required|string|max:20',
            'duration'         => 'required|integer|min:1',
            'duration_type'    => 'required|in:year,month',
            'structure_type'   => 'required|in:semester,yearly,modular',
            'max_atkt_allowed' => 'required|integer|min:0|max:10',
        ]);

        $oldDuration      = $course->duration;
        $oldStructure     = $course->structure_type;
        $newDuration      = (int) $request->duration;
        $newStructure     = $request->structure_type;

        DB::transaction(function () use ($request, $course, $oldDuration, $oldStructure, $newDuration, $newStructure) {

            $course->update([
                'course_type_id'           => $request->course_type_id,
                'name'                     => strtoupper($request->name),
                'code'                     => strtoupper($request->code),
                'duration'                 => $newDuration,
                'duration_type'            => $request->duration_type,
                'structure_type'           => $newStructure,
                'max_atkt_allowed'         => $request->max_atkt_allowed,
                'lateral_entry_allowed'    => $request->boolean('lateral_entry_allowed'),
                'lateral_entry_start_part' => $request->lateral_entry_start_part,
            ]);

            if ($oldDuration !== $newDuration || $oldStructure !== $newStructure) {
                // Only add missing parts — do not delete existing ones (students may be enrolled)
                self::generateCourseParts($course, onlyMissing: true);
            }
        });

        return redirect()->route('master.courses.index')
            ->with('success', 'Course updated successfully!');
    }

    public function destroy(Course $course)
    {
        $this->authorizeCourse($course);

        if ($course->streams()->exists()) {
            return back()->withErrors(['delete' => "Cannot delete \"{$course->name}\" — remove its streams first."]);
        }

        $course->parts()->delete();
        $course->delete();
        return redirect()->route('master.courses.index')
            ->with('success', 'Course deleted!');
    }

    public function toggleStatus(Course $course)
    {
        $this->authorizeCourse($course);
        $course->update(['status' => !$course->status]);
        return back()->with('success', 'Status updated!');
    }

    // ── Auto-generate CourseParts ────────────────────────────────────────
    // Generates Year/Semester parts based on duration and structure_type.
    //
    // Examples:
    //   BA (3 year, yearly)   → Year 1, Year 2, Year 3
    //   BA (3 year, semester) → Sem 1, Sem 2, Sem 3, Sem 4, Sem 5, Sem 6
    //   MA (2 year, semester) → Sem 1, Sem 2, Sem 3, Sem 4
    //
    // $onlyMissing = true  → add only parts that don't exist yet (used on update)
    // $onlyMissing = false → create all parts (used on initial creation)
    public static function generateCourseParts(Course $course, bool $onlyMissing = false): void
    {
        $duration  = (int) $course->duration;
        $structure = $course->structure_type; // semester | yearly | modular

        $parts = [];

        if ($structure === 'semester') {
            // month-type: 6 months = 1 semester; year-type: 1 year = 2 semesters
            $totalSems = $course->duration_type === 'month'
                ? max(1, (int) ceil($duration / 6))
                : $duration * 2;
            for ($sem = 1; $sem <= $totalSems; $sem++) {
                $year = (int) ceil($sem / 2); // sem 1,2 = year 1; sem 3,4 = year 2; etc.
                $parts[] = [
                    'part_number' => $sem,
                    'part_name'   => "Semester {$sem}",
                    'year_number' => $year,
                ];
            }
        } else {
            // yearly / modular — one part per year
            // month-type: 12 months = 1 year part
            $totalYears = $course->duration_type === 'month'
                ? max(1, (int) ceil($duration / 12))
                : $duration;
            for ($y = 1; $y <= $totalYears; $y++) {
                $suffix = match($y) { 1 => '1st', 2 => '2nd', 3 => '3rd', default => "{$y}th" };
                $parts[] = [
                    'part_number' => $y,
                    'part_name'   => "Year {$y} ({$suffix} Year)",
                    'year_number' => $y,
                ];
            }
        }

        foreach ($parts as $part) {
            if ($onlyMissing) {
                CoursePart::firstOrCreate(
                    ['course_id' => $course->id, 'part_number' => $part['part_number']],
                    ['part_name' => $part['part_name'], 'year_number' => $part['year_number'], 'status' => true]
                );
            } else {
                CoursePart::create([
                    'course_id'   => $course->id,
                    'part_number' => $part['part_number'],
                    'part_name'   => $part['part_name'],
                    'year_number' => $part['year_number'],
                    'status'      => true,
                ]);
            }
        }
    }

    private function authorizeCourse(Course $course): void
    {
        abort_if($course->institute_id !== auth()->user()->institute_id, 403);
    }
}