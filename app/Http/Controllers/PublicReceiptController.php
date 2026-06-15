<?php

namespace App\Http\Controllers;

use App\Models\Institute;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Services\WalletService;
use App\Traits\BuildsStudentStatements;
use Illuminate\Http\Request;

class PublicReceiptController extends Controller
{
    use BuildsStudentStatements;

    private function verify(Request $request, string $type): array
    {
        $sid = (int) $request->sid;
        $iid = (int) $request->iid;
        $sig = (string) $request->sig;

        abort_if(!$sid || !$iid || !$sig, 404);

        $expected = substr(hash_hmac('sha256', "{$sid}:{$iid}:{$type}", config('app.key')), 0, 32);
        abort_if(!hash_equals($expected, $sig), 403, 'Invalid or expired receipt link.');

        return ['sid' => $sid, 'iid' => $iid];
    }

    public function balance(Request $request)
    {
        ['sid' => $sid, 'iid' => $iid] = $this->verify($request, 'balance');

        $student = Student::with(['stream.course', 'coursePart', 'session'])
            ->where('institute_id', $iid)
            ->findOrFail($sid);

        $context = WalletService::resolveAcademicContext($student, (int) $student->academic_session_id);
        if (!empty($context['course_part'])) {
            $student->setRelation('coursePart', $context['course_part']);
        }

        return view('institute.statement.balance-print', [
            'student'    => $student,
            'balances'   => $this->buildBalances($student, $iid),
            'institute'  => Institute::findOrFail($iid),
            'printMode'  => 'thermal',
            'printedBy'  => null,
            'receiptUrl' => url()->full(),
            'autoprint'  => false,
        ]);
    }

    public function record(Request $request)
    {
        ['sid' => $sid, 'iid' => $iid] = $this->verify($request, 'record');

        $student = Student::with(['stream.course', 'coursePart', 'session'])
            ->where('institute_id', $iid)
            ->findOrFail($sid);

        $context = WalletService::resolveAcademicContext($student, (int) $student->academic_session_id);
        if (!empty($context['course_part'])) {
            $student->setRelation('coursePart', $context['course_part']);
        }

        $subjectNames = StudentSubject::where('student_id', $student->id)
            ->where('academic_session_id', $student->academic_session_id)
            ->with('subject')
            ->get()
            ->pluck('subject.name')
            ->filter()
            ->values();

        return view('institute.statement.record-print', [
            'student'      => $student,
            'history'      => $this->buildHistory($student, $iid),
            'institute'    => Institute::findOrFail($iid),
            'printMode'    => 'thermal',
            'subjectNames' => $subjectNames,
            'printedBy'    => null,
            'receiptUrl'   => url()->full(),
            'autoprint'    => false,
        ]);
    }
}
