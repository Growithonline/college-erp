<?php

namespace App\Http\Controllers\Institute\Fee;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentFeeStatementService;
use Illuminate\Http\Request;

/**
 * Full year-wise fee statement of one student (on screen, print and PDF).
 * Available to the institute admin and to staff who may view fee history.
 */
class StudentFeeStatementController extends Controller
{
    private function authorizeStudent(Student $student)
    {
        $staff = auth()->guard('staff')->user();
        $user  = $staff ?: auth()->guard('web')->user();

        abort_if(!$user || !$user->institute_id || (int) $student->institute_id !== (int) $user->institute_id, 403);

        if ($staff) {
            abort_if(!$staff->canViewFeeHistory(), 403, 'Fee history permission required.');
            abort_if(!$staff->canAccessStudentForOperations($student), 403, 'This student is outside your access scope.');
        }

        return $staff;
    }

    private function data(Request $request, Student $student): array
    {
        $asOf = $request->filled('session_id') ? (int) $request->input('session_id') : null;

        return StudentFeeStatementService::build($student, $asOf) + [
            'isStaff'     => auth()->guard('staff')->check(),
            'printedAt'   => now()->format('d M Y h:i A'),
        ];
    }

    public function show(Request $request, Student $student)
    {
        $staff = $this->authorizeStudent($student);

        return view('institute.fee.student-statement', $this->data($request, $student) + [
            'pdf'        => false,
            'autoPrint'  => $request->boolean('print'),
            'selfRoute'  => $staff ? 'staff.fee.student-statement' : 'fee.student-statement',
            'pdfRoute'   => $staff ? 'staff.fee.student-statement-pdf' : 'fee.student-statement-pdf',
            'backUrl'    => url()->previous(),
        ]);
    }

    public function pdf(Request $request, Student $student)
    {
        $this->authorizeStudent($student);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('institute.fee.student-statement', $this->data($request, $student) + [
            'pdf'       => true,
            'autoPrint' => false,
            'selfRoute' => null,
            'pdfRoute'  => null,
            'backUrl'   => null,
        ]);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'dpi' => 96]);

        $name = 'fee-statement-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $student->student_uid) . '.pdf';

        return $pdf->download($name);
    }
}
