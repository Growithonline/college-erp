<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\Library\LibraryBookCopy;
use App\Models\Library\LibraryFinePayment;
use App\Models\Library\LibraryMember;
use App\Models\Library\LibraryTransaction;
use App\Services\LibraryManagementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LibraryCirculationController extends BaseLibraryController
{
    public function index(Request $request)
    {
        $this->ensureLibraryPermission('issue');
        $instituteId = $this->instituteId();
        LibraryManagementService::expireReservations($instituteId);
        $memberSearch = trim((string) $request->input('member_search', ''));
        $copySearch = trim((string) $request->input('copy_search', ''));
        $memberLike = $this->escapeLike($memberSearch);
        $copyLike   = $this->escapeLike($copySearch);

        $members = LibraryMember::forInstitute($instituteId)
            ->with(['ruleSet', 'activeTransactions.copy.book', 'transactions'])
            ->when($memberSearch !== '', function ($query) use ($memberLike) {
                $query->where(function ($builder) use ($memberLike) {
                    $builder->where('member_code', 'like', '%' . $memberLike . '%')
                        ->orWhere('name', 'like', '%' . $memberLike . '%')
                        ->orWhere('mobile', 'like', '%' . $memberLike . '%');
                });
            })
            ->orderBy('name')
            ->limit(10)
            ->get();

        $copies = LibraryBookCopy::forInstitute($instituteId)
            ->with(['book', 'rack', 'vendor'])
            ->when($copySearch !== '', function ($query) use ($copyLike) {
                $query->where(function ($builder) use ($copyLike) {
                    $builder->where('accession_no', 'like', '%' . $copyLike . '%')
                        ->orWhere('barcode', 'like', '%' . $copyLike . '%')
                        ->orWhereHas('book', fn($bookQuery) => $bookQuery->where('title', 'like', '%' . $copyLike . '%'));
                });
            })
            ->orderBy('accession_no')
            ->limit(10)
            ->get();

        $activeTransactions = LibraryTransaction::forInstitute($instituteId)
            ->with(['member', 'copy.book'])
            ->where('current_status', 'issued')
            ->latest('issued_on')
            ->paginate(20)
            ->withQueryString();

        $fineTransactions = LibraryTransaction::forInstitute($instituteId)
            ->with(['member', 'copy.book'])
            ->whereRaw('fine_amount > fine_paid')
            ->latest('id')
            ->limit(10)
            ->get();

        $stats = [
            'issued_today' => LibraryTransaction::forInstitute($instituteId)->whereDate('issued_on', now()->toDateString())->count(),
            'returned_today' => LibraryTransaction::forInstitute($instituteId)->whereDate('returned_on', now()->toDateString())->count(),
            'overdue' => LibraryTransaction::forInstitute($instituteId)->where('current_status', 'issued')->whereDate('due_on', '<', now()->toDateString())->count(),
            'fine_due' => (float) LibraryTransaction::forInstitute($instituteId)->selectRaw('COALESCE(SUM(fine_amount - fine_paid), 0) as amount')->value('amount'),
        ];

        return view('institute.library.circulation.index', compact('members', 'copies', 'activeTransactions', 'fineTransactions', 'memberSearch', 'copySearch', 'stats'));
    }

    public function issue(Request $request)
    {
        $this->ensureLibraryPermission('issue');
        $data = $request->validate([
            'library_member_id' => 'required|integer',
            'library_book_copy_id' => 'required|integer',
            'issued_on' => 'required|date|before_or_equal:today',
            'remarks' => 'nullable|string|max:255',
        ]);

        $instituteId = $this->instituteId();

        DB::transaction(function () use ($data, $instituteId) {
            $member = LibraryMember::forInstitute($instituteId)
                ->with(['ruleSet', 'activeTransactions'])
                ->findOrFail($data['library_member_id']);

            $copy = LibraryBookCopy::forInstitute($instituteId)
                ->with('book')
                ->findOrFail($data['library_book_copy_id']);

            $rule = LibraryManagementService::ensureMemberCanBorrow($member);
            LibraryManagementService::ensureCopyCanBeIssuedToMember($copy, $member);

            $issuedOn = Carbon::parse($data['issued_on']);

            LibraryTransaction::create([
                'institute_id' => $instituteId,
                'library_member_id' => $member->id,
                'library_book_copy_id' => $copy->id,
                'academic_session_id' => $this->activeSessionId(),
                'txn_type' => 'issue',
                'current_status' => 'issued',
                'issued_on' => $issuedOn->toDateString(),
                'due_on' => $issuedOn->copy()->addDays($rule->loan_days)->toDateString(),
                'loan_days_snapshot' => $rule->loan_days,
                'fine_per_day_snapshot' => $rule->fine_per_day,
                'grace_days_snapshot' => $rule->grace_days,
                'max_renewals_snapshot' => $rule->max_renewals,
                'rule_name_snapshot' => $rule->name,
                'remarks' => trim((string) ($data['remarks'] ?? '')) ?: null,
                'issued_by' => $this->actorName(),
            ]);

            $copy->update(['status' => 'issued']);

            $reservation = LibraryManagementService::firstActiveReservation((int) $copy->book_id, $instituteId);
            if ($reservation && (int) $reservation->library_member_id === (int) $member->id) {
                $reservation->update([
                    'status' => 'fulfilled',
                    'fulfilled_copy_id' => $copy->id,
                ]);
            }
        });

        return back()->with('success', 'Book issued successfully.');
    }

    public function renew(Request $request, LibraryTransaction $transaction)
    {
        $this->ensureLibraryPermission('issue');
        abort_if($transaction->institute_id !== $this->instituteId(), 403);

        LibraryManagementService::ensureTransactionCanRenew($transaction->loadMissing(['member', 'copy.book']));

        $renewBase = Carbon::parse($transaction->due_on)->max(Carbon::today());

        $transaction->update([
            'renew_count' => $transaction->renew_count + 1,
            'due_on' => $renewBase->addDays((int) $transaction->loan_days_snapshot)->toDateString(),
        ]);

        return back()->with('success', 'Book renewed successfully.');
    }

    public function return(Request $request, LibraryTransaction $transaction)
    {
        $this->ensureLibraryPermission('issue');
        abort_if($transaction->institute_id !== $this->instituteId(), 403);

        $issuedOnDate = Carbon::parse($transaction->issued_on)->toDateString();

        $data = $request->validate([
            'returned_on'   => ['required', 'date', 'before_or_equal:today', "after_or_equal:{$issuedOnDate}"],
            'return_mode'   => 'nullable|in:returned,lost,damaged',
            'penalty_amount' => 'nullable|numeric|min:0',
            'remarks'       => 'nullable|string|max:255',
        ]);

        if ($transaction->current_status !== 'issued') {
            return back()->withErrors(['return' => 'This transaction is already closed.']);
        }

        DB::transaction(function () use ($transaction, $data) {
            $returnedOn = Carbon::parse($data['returned_on']);
            LibraryManagementService::finalizeReturn(
                $transaction,
                $returnedOn,
                $data['return_mode'] ?? 'returned',
                (float) ($data['penalty_amount'] ?? 0),
                $data['remarks'] ?? null,
                $this->actorName()
            );
        });

        return back()->with('success', 'Book returned successfully.');
    }

    public function payFine(Request $request, LibraryTransaction $transaction)
    {
        $this->ensureLibraryPermission('issue');
        abort_if($transaction->institute_id !== $this->instituteId(), 403);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|string|max:30',
            'payment_date' => 'required|date|before_or_equal:today',
            'receipt_no'   => 'nullable|string|max:80',
            'remarks' => 'nullable|string|max:255',
        ]);

        $instituteId = $this->instituteId();
        DB::transaction(function () use ($transaction, $data, $instituteId) {
            $tx = LibraryTransaction::where('id', $transaction->id)
                ->where('institute_id', $instituteId)
                ->lockForUpdate()
                ->firstOrFail();

            $pendingFine = max(0, (float) $tx->fine_amount - (float) $tx->fine_paid);

            if ($pendingFine <= 0) {
                throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'This transaction has no pending fine.']);
            }

            if ((float) $data['amount'] > $pendingFine) {
                throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Payment amount cannot exceed the pending fine.']);
            }

            LibraryFinePayment::create([
                'institute_id' => $this->instituteId(),
                'library_member_id' => $tx->library_member_id,
                'library_transaction_id' => $tx->id,
                'amount' => $data['amount'],
                'payment_mode' => $data['payment_mode'],
                'payment_date' => $data['payment_date'],
                'receipt_no' => trim((string) ($data['receipt_no'] ?? '')) ?: null,
                'remarks' => trim((string) ($data['remarks'] ?? '')) ?: null,
                'collected_by' => $this->actorName(),
            ]);

            $tx->update([
                'fine_paid' => (float) $tx->fine_paid + (float) $data['amount'],
            ]);

            LibraryManagementService::postFinePaymentToWallet($tx->loadMissing(['member.student', 'copy.book']), (float) $data['amount'], $data['payment_date']);
        });

        return back()->with('success', 'Fine payment recorded successfully.');
    }

    public function receipt(LibraryTransaction $transaction)
    {
        $this->ensureLibraryPermission('issue');
        abort_if($transaction->institute_id !== $this->instituteId(), 403);

        $transaction->load(['member', 'copy.book']);

        return view('institute.library.circulation.receipt', compact('transaction'));
    }
}
