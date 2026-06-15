<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\AcademicSession;
use App\Models\InstituteBankAccount;
use App\Models\Library\LibraryFinePayment;
use App\Models\Library\LibraryMember;
use App\Models\Library\LibraryTransaction;
use App\Services\JournalService;
use App\Services\LibraryManagementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LibraryFineCollectionController extends BaseLibraryController
{
    public function index(Request $request)
    {
        $this->ensureLibraryPermission('issue');
        $instituteId = $this->instituteId();
        $search      = trim((string) $request->input('search', ''));

        $stats = LibraryTransaction::forInstitute($instituteId)
            ->selectRaw("COUNT(DISTINCT library_member_id) as members_with_fines, COALESCE(SUM(fine_amount - fine_paid), 0) as total_pending")
            ->whereRaw('fine_amount > fine_paid')
            ->first();

        $collectedToday = (float) LibraryFinePayment::where('institute_id', $instituteId)
            ->whereDate('payment_date', now()->toDateString())
            ->sum('amount');

        $members = LibraryMember::forInstitute($instituteId)
            ->with(['student', 'staffMember.role'])
            ->whereHas('transactions', fn($q) => $q->whereRaw('fine_amount > fine_paid'))
            ->when($search !== '', fn($q) => $q->where(fn($b) =>
                $b->where('member_code', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%')
                  ->orWhere('mobile', 'like', '%' . $search . '%')
            ))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $members->each(fn($member) => $member->pending_fine_total = LibraryManagementService::memberOutstandingFine($member));

        return view('institute.library.fines.index', compact('members', 'search', 'stats', 'collectedToday'));
    }

    public function show(LibraryMember $member)
    {
        $this->ensureLibraryPermission('issue');
        abort_if($member->institute_id !== $this->instituteId(), 403);

        $member->load(['student', 'staffMember.role']);

        $pendingTransactions = $member->transactions()
            ->with(['copy.book'])
            ->whereRaw('fine_amount > fine_paid')
            ->orderBy('issued_on')
            ->get()
            ->each(fn($tx) => $tx->pending_fine = max(0, (float) $tx->fine_amount - (float) $tx->fine_paid));

        $totalPending           = $pendingTransactions->sum('pending_fine');
        $nextReceiptNo          = 'LIB-RCP-' . now()->format('Ymd') . '-' . $member->id;
        $defaultPaymentDatetime = now()->format('Y-m-d\TH:i');

        $bankAccounts = InstituteBankAccount::where('institute_id', $this->instituteId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('bank_name')
            ->get();

        return view('institute.library.fines.show', compact(
            'member', 'pendingTransactions', 'totalPending',
            'nextReceiptNo', 'defaultPaymentDatetime', 'bankAccounts'
        ));
    }

    public function collect(Request $request, LibraryMember $member)
    {
        $this->ensureLibraryPermission('issue');
        abort_if($member->institute_id !== $this->instituteId(), 403);

        $mode      = $request->input('payment_mode', 'cash');
        $isNonCash = $mode !== 'cash';

        $data = $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.transaction_id' => 'required|integer',
            'items.*.amount'         => 'required|numeric|min:0',
            'payment_mode'           => 'required|string|max:30',
            'payment_date'           => 'required_if:payment_mode,cash|nullable|date',
            'payment_datetime'       => 'required_unless:payment_mode,cash|nullable|date_format:Y-m-d\TH:i',
            'bank_account_id'        => 'nullable|integer|exists:institute_bank_accounts,id',
            'transaction_ref'        => 'required_if:payment_mode,upi,online,neft,rtgs,cheque,dd|nullable|string|max:100',
            'bank_name'              => 'nullable|string|max:100',
            'receipt_no'             => 'nullable|string|max:80',
            'remarks'                => 'nullable|string|max:255',
        ]);

        $paymentDatetime = $isNonCash && !empty($data['payment_datetime'])
            ? Carbon::parse($data['payment_datetime'])
            : null;

        $paymentDate = $paymentDatetime
            ? $paymentDatetime->toDateString()
            : ($data['payment_date'] ?? now()->toDateString());

        $totalAmount = collect($data['items'])->sum(fn($i) => (float) $i['amount']);

        if ($totalAmount <= 0) {
            return back()->withErrors(['items' => 'Kam se kam ek item ka amount enter karo.']);
        }

        $receiptNo = trim((string) ($data['receipt_no'] ?? ''))
            ?: 'LIB-RCP-' . now()->format('Ymd') . '-' . $member->id . '-' . strtoupper(substr(uniqid(), -6));

        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->first();

        DB::transaction(function () use ($data, $member, $receiptNo, $totalAmount, $paymentDate, $paymentDatetime) {
            foreach ($data['items'] as $item) {
                $amount = (float) $item['amount'];
                if ($amount <= 0) {
                    continue;
                }

                $tx = LibraryTransaction::where('id', (int) $item['transaction_id'])
                    ->where('library_member_id', $member->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $pending = max(0, (float) $tx->fine_amount - (float) $tx->fine_paid);
                if ($amount > $pending + 0.001) {
                    throw ValidationException::withMessages(['items' => 'Ek book ka amount pending fine se zyada hai.']);
                }

                LibraryFinePayment::create([
                    'institute_id'           => $this->instituteId(),
                    'library_member_id'      => $member->id,
                    'library_transaction_id' => $tx->id,
                    'amount'                 => $amount,
                    'payment_mode'           => $data['payment_mode'],
                    'bank_account_id'        => $data['bank_account_id'] ?? null,
                    'transaction_ref'        => trim((string) ($data['transaction_ref'] ?? '')) ?: null,
                    'bank_name'              => trim((string) ($data['bank_name'] ?? '')) ?: null,
                    'payment_date'           => $paymentDate,
                    'payment_datetime'       => $paymentDatetime,
                    'receipt_no'             => $receiptNo,
                    'remarks'                => trim((string) ($data['remarks'] ?? '')) ?: null,
                    'collected_by'           => $this->actorName(),
                ]);

                $tx->update(['fine_paid' => (float) $tx->fine_paid + $amount]);
            }

            $description = 'Library fine collected - ' . $member->name . ' (' . $member->member_code . ')';
            LibraryManagementService::postBulkFinePaymentToWallets(
                $member->loadMissing('student'),
                $totalAmount,
                $paymentDate,
                $description
            );
        });

        JournalService::safePostLibraryFineCollection(
            instituteId:       $instituteId,
            totalAmount:       $totalAmount,
            paymentMode:       $data['payment_mode'],
            bankAccountId:     $data['bank_account_id'] ?? null,
            paymentDate:       $paymentDate,
            receiptNo:         $receiptNo,
            narration:         'Library fine collected - ' . $member->name . ' (' . $member->member_code . ')',
            academicSessionId: $activeSession?->id,
        );

        return redirect()->route($this->routeName('fines.receipt'), [$member->id, urlencode($receiptNo)])
            ->with('success', 'Fine collect ho gayi. Receipt ready hai.');
    }

    public function receipt(LibraryMember $member, string $receiptNo)
    {
        $this->ensureLibraryPermission('issue');
        abort_if($member->institute_id !== $this->instituteId(), 403);

        $member->load(['student.session', 'staffMember.role']);

        $payments = LibraryFinePayment::where('institute_id', $this->instituteId())
            ->where('library_member_id', $member->id)
            ->where('receipt_no', urldecode($receiptNo))
            ->with(['transaction.copy.book'])
            ->orderBy('id')
            ->get();

        abort_if($payments->isEmpty(), 404);

        $totalCollected = $payments->sum('amount');
        $firstPayment   = $payments->first();

        return view('institute.library.fines.receipt', compact('member', 'payments', 'totalCollected', 'firstPayment', 'receiptNo'));
    }
}
