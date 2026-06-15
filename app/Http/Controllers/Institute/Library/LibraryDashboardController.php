<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\Library\LibraryBook;
use App\Models\Library\LibraryBookCopy;
use App\Models\Library\LibraryCategory;
use App\Models\Library\LibraryMember;
use App\Models\Library\LibraryTransaction;

class LibraryDashboardController extends BaseLibraryController
{
    public function index()
    {
        $this->ensureLibraryPermission('view');
        $instituteId = $this->instituteId();

        $copyStats = LibraryBookCopy::forInstitute($instituteId)
            ->selectRaw("COUNT(*) as total, COUNT(CASE WHEN status = 'available' THEN 1 END) as available, COUNT(CASE WHEN status = 'issued' THEN 1 END) as issued")
            ->first();

        $memberStats = LibraryMember::forInstitute($instituteId)
            ->selectRaw("COUNT(*) as total, COUNT(CASE WHEN status = 'blocked' THEN 1 END) as blocked")
            ->first();

        $txnStats = LibraryTransaction::forInstitute($instituteId)
            ->selectRaw("COUNT(CASE WHEN current_status = 'issued' AND due_on < ? THEN 1 END) as overdue, COALESCE(SUM(fine_amount - fine_paid), 0) as fine_due", [now()->toDateString()])
            ->first();

        $stats = [
            'titles'          => LibraryBook::forInstitute($instituteId)->count(),
            'copies'          => (int)   ($copyStats->total    ?? 0),
            'available'       => (int)   ($copyStats->available ?? 0),
            'issued'          => (int)   ($copyStats->issued    ?? 0),
            'members'         => (int)   ($memberStats->total   ?? 0),
            'blocked_members' => (int)   ($memberStats->blocked ?? 0),
            'overdue'         => (int)   ($txnStats->overdue    ?? 0),
            'fine_due'        => (float) ($txnStats->fine_due   ?? 0),
        ];

        $recentTransactions = LibraryTransaction::forInstitute($instituteId)
            ->with(['member', 'copy.book'])
            ->latest('id')
            ->limit(10)
            ->get();

        $overdueTransactions = LibraryTransaction::forInstitute($instituteId)
            ->with(['member', 'copy.book'])
            ->where('current_status', 'issued')
            ->whereDate('due_on', '<', now()->toDateString())
            ->orderBy('due_on')
            ->limit(10)
            ->get();

        $categoryStats = LibraryCategory::forInstitute($instituteId)
            ->withCount('books')
            ->orderByDesc('books_count')
            ->limit(6)
            ->get();

        return view('institute.library.dashboard.index', compact(
            'stats',
            'recentTransactions',
            'overdueTransactions',
            'categoryStats'
        ));
    }
}
