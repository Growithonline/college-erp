<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\Library\LibraryBookCopy;
use App\Models\Library\LibraryFinePayment;
use App\Models\Library\LibraryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LibraryReportController extends BaseLibraryController
{
    public function index(Request $request)
    {
        $this->ensureLibraryPermission('reports');
        $instituteId = $this->instituteId();
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $overdues = LibraryTransaction::forInstitute($instituteId)
            ->with(['member', 'copy.book'])
            ->where('current_status', 'issued')
            ->whereDate('due_on', '<', now()->toDateString())
            ->orderBy('due_on')
            ->limit(200)
            ->get();

        $issuedToday = LibraryTransaction::forInstitute($instituteId)
            ->with(['member', 'copy.book'])
            ->whereDate('issued_on', now()->toDateString())
            ->latest('id')
            ->limit(25)
            ->get();

        $returnedToday = LibraryTransaction::forInstitute($instituteId)
            ->with(['member', 'copy.book'])
            ->whereDate('returned_on', now()->toDateString())
            ->latest('id')
            ->limit(25)
            ->get();

        $fineCollections = LibraryFinePayment::forInstitute($instituteId)
            ->with(['member', 'transaction.copy.book'])
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo)
            ->latest('payment_date')
            ->get();

        $inventory = [
            'available' => LibraryBookCopy::forInstitute($instituteId)->where('status', 'available')->count(),
            'issued' => LibraryBookCopy::forInstitute($instituteId)->where('status', 'issued')->count(),
            'lost' => LibraryBookCopy::forInstitute($instituteId)->where('status', 'lost')->count(),
            'damaged' => LibraryBookCopy::forInstitute($instituteId)->where('status', 'damaged')->count(),
            'withdrawn' => LibraryBookCopy::forInstitute($instituteId)->where('status', 'withdrawn')->count(),
        ];

        $memberHistory = LibraryTransaction::forInstitute($instituteId)
            ->with(['member', 'copy.book'])
            ->whereDate('issued_on', '>=', $dateFrom)
            ->whereDate('issued_on', '<=', $dateTo)
            ->latest('issued_on')
            ->limit(50)
            ->get();

        $lostDamaged = LibraryTransaction::forInstitute($instituteId)
            ->with(['member', 'copy.book'])
            ->whereIn('current_status', ['lost', 'damaged'])
            ->latest('returned_on')
            ->limit(25)
            ->get();

        $courseUsage = DB::table('library_transactions as lt')
            ->join('library_members as lm', 'lt.library_member_id', '=', 'lm.id')
            ->leftJoin('students as s', 'lm.student_id', '=', 's.id')
            ->leftJoin('course_streams as cs', 's.course_stream_id', '=', 'cs.id')
            ->leftJoin('courses as c', 'cs.course_id', '=', 'c.id')
            ->where('lt.institute_id', $instituteId)
            ->whereDate('lt.issued_on', '>=', $dateFrom)
            ->whereDate('lt.issued_on', '<=', $dateTo)
            ->selectRaw("COALESCE(c.name, 'Staff / Faculty') as course, COUNT(*) as issues")
            ->groupByRaw("COALESCE(c.name, 'Staff / Faculty')")
            ->orderByDesc('issues')
            ->get()
            ->map(fn($row) => ['course' => $row->course, 'issues' => $row->issues])
            ->values();

        $stockVerification = LibraryBookCopy::forInstitute($instituteId)
            ->with(['book', 'rack'])
            ->orderBy('status')
            ->orderBy('accession_no')
            ->limit(100)
            ->get();

        $totalFineCollected = (float) $fineCollections->sum('amount');

        return view('institute.library.reports.index', compact(
            'overdues',
            'issuedToday',
            'returnedToday',
            'fineCollections',
            'inventory',
            'memberHistory',
            'lostDamaged',
            'courseUsage',
            'stockVerification',
            'dateFrom',
            'dateTo',
            'totalFineCollected'
        ));
    }
}
