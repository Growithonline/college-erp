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

        $search     = trim((string) $request->input('search', ''));
        $searchLike = $this->escapeLike($search);
        $students   = collect();

        if ($search !== '') {
            $students = Student::where('institute_id', $this->instituteId())
                ->with(['stream.course', 'libraryMember.activeTransactions.copy.book', 'libraryMember.transactions'])
                ->where(function ($query) use ($searchLike) {
                    $query->where('name', 'like', '%' . $searchLike . '%')
                        ->orWhere('student_uid', 'like', '%' . $searchLike . '%')
                        ->orWhere('mobile', 'like', '%' . $searchLike . '%')
                        ->orWhere('enrollment_no', 'like', '%' . $searchLike . '%')
                        ->orWhere('roll_no', 'like', '%' . $searchLike . '%');
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
