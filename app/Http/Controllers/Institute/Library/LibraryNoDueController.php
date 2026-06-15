<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\Student;
use App\Services\LibraryManagementService;
use Illuminate\Http\Request;

class LibraryNoDueController extends BaseLibraryController
{
    public function index(Request $request)
    {
        $this->ensureLibraryPermission('no_due');

        $search = trim((string) $request->input('search', ''));
        $students = collect();

        if ($search !== '') {
            $students = Student::where('institute_id', $this->instituteId())
                ->with(['stream.course', 'libraryMember.activeTransactions.copy.book', 'libraryMember.transactions'])
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('student_uid', 'like', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%')
                        ->orWhere('enrollment_no', 'like', '%' . $search . '%')
                        ->orWhere('roll_no', 'like', '%' . $search . '%');
                })
                ->orderBy('name')
                ->limit(25)
                ->get();
        }

        $summaries = $students->mapWithKeys(fn(Student $student) => [
            $student->id => LibraryManagementService::noDueSummaryForStudent($student),
        ]);

        return view('institute.library.no-due.index', compact('students', 'search', 'summaries'));
    }

    public function print(Student $student)
    {
        $this->ensureLibraryPermission('no_due');
        abort_if($student->institute_id !== $this->instituteId(), 403);

        $student->load(['stream.course', 'libraryMember.activeTransactions.copy.book', 'libraryMember.transactions']);
        $summary = LibraryManagementService::noDueSummaryForStudent($student);

        return view('institute.library.no-due.print', compact('student', 'summary'));
    }
}
