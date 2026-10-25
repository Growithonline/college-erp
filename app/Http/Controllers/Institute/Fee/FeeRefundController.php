<?php

namespace App\Http\Controllers\Institute\Fee;

use App\Exceptions\InsufficientWalletBalanceException;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\FeeRefund;
use App\Models\InstituteBankAccount;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use App\Services\AuditLogService;
use App\Services\InstituteWalletService;
use App\Services\JournalService;
use App\Traits\ExportsTabularData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

// Refund portal: cash a student paid beyond their fees, owed back when they leave (raised by the final
// session promotion — see AdvanceService::settle()). Institute admin only: staff can neither approve
// nor pay a refund. Flow: pending -> approved -> paid (voucher, student wallet, institute wallet and a
// journal entry all in one transaction), or rejected. The list exports to CSV for the accountant.
class FeeRefundController extends Controller
{
    use ExportsTabularData;

    private const PAYMENT_MODES = [
        'cash'          => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'upi'           => 'UPI',
        'cheque'        => 'Cheque',
    ];

    private function instituteId(): int
    {
        abort_unless(auth()->guard('web')->check(), 403, 'Only the institute admin can manage refunds.');

        return (int) auth()->user()->institute_id;
    }

    private function scoped(FeeRefund $refund): FeeRefund
    {
        abort_if((int) $refund->institute_id !== $this->instituteId(), 403);

        return $refund;
    }

    private function query(Request $request)
    {
        $query = FeeRefund::with(['student:id,name,student_uid', 'session:id,name'])
            ->where('institute_id', $this->instituteId());

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('session_id')) {
            $query->where('academic_session_id', (int) $request->input('session_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }
        if ($request->filled('q')) {
            $s = addcslashes((string) $request->input('q'), '%_\\');
            $query->whereHas('student', fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('student_uid', 'like', "%{$s}%"));
        }

        return $query->orderByDesc('id');
    }

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();

        if ($request->boolean('export')) {
            return $this->export($request);
        }

        $refunds = $this->query($request)->paginate(25)->withQueryString();

        $totals = FeeRefund::where('institute_id', $instituteId)
            ->selectRaw('status, COUNT(*) as n, SUM(amount) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return view('institute.fee.refunds.index', [
            'refunds'      => $refunds,
            'totals'       => $totals,
            'sessions'     => AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get(['id', 'name']),
            'bankAccounts' => InstituteBankAccount::where('institute_id', $instituteId)->where('is_active', true)->orderBy('sort_order')->get(),
            'modes'        => self::PAYMENT_MODES,
        ]);
    }

    public function approve(FeeRefund $refund)
    {
        $this->scoped($refund);

        $updated = FeeRefund::where('id', $refund->id)->where('status', FeeRefund::PENDING)->update([
            'status'      => FeeRefund::APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        if (!$updated) {
            return back()->withErrors(['refund' => 'This refund is no longer pending.']);
        }

        AuditLogService::log($refund->institute_id, 'fee_refund', 'approved', 'Refund approved: ' . number_format((float) $refund->amount, 2), $refund);

        return back()->with('success', 'Refund approved. Record the payment once the money is handed over.');
    }

    public function reject(Request $request, FeeRefund $refund)
    {
        $this->scoped($refund);
        $data = $request->validate(['remarks' => ['required', 'string', 'max:255']]);

        $updated = FeeRefund::where('id', $refund->id)
            ->whereIn('status', [FeeRefund::PENDING, FeeRefund::APPROVED])
            ->update(['status' => FeeRefund::REJECTED, 'remarks' => $data['remarks']]);

        if (!$updated) {
            return back()->withErrors(['refund' => 'This refund can no longer be rejected.']);
        }

        AuditLogService::log($refund->institute_id, 'fee_refund', 'rejected', 'Refund rejected: ' . $data['remarks'], $refund);

        return back()->with('success', 'Refund rejected. The amount stays on the student\'s account as an advance liability.');
    }

    public function pay(Request $request, FeeRefund $refund)
    {
        $instituteId = $this->instituteId();
        $this->scoped($refund);

        $data = $request->validate([
            'payment_mode'    => ['required', Rule::in(array_keys(self::PAYMENT_MODES))],
            'bank_account_id' => ['nullable', Rule::exists('institute_bank_accounts', 'id')->where('institute_id', $instituteId)],
            'reference_no'    => ['nullable', 'string', 'max:100'],
            'paid_on'         => ['required', 'date', 'before_or_equal:today'],
        ]);

        if ($data['payment_mode'] !== 'cash' && empty($data['bank_account_id'])) {
            return back()->withErrors(['bank_account_id' => 'Select the bank account the refund was paid from.'])->withInput();
        }

        try {
            DB::transaction(function () use ($refund, $data, $instituteId) {
                $locked = FeeRefund::where('id', $refund->id)->lockForUpdate()->first();
                if (!$locked || $locked->status !== FeeRefund::APPROVED) {
                    throw new \DomainException('Only an approved refund can be paid.');
                }

                $student = $locked->student()->first();
                $amount  = round((float) $locked->amount, 2);

                $wallet = StudentWallet::where('student_id', $locked->student_id)
                    ->where('academic_session_id', $locked->academic_session_id)
                    ->lockForUpdate()
                    ->first();
                if (!$wallet || (float) $wallet->main_b + 0.005 < $amount) {
                    throw new \DomainException('The student wallet no longer holds this much credit (' . number_format((float) ($wallet->main_b ?? 0), 2) . ').');
                }

                $voucherNo = 'RFD/' . now()->format('Y') . '/' . str_pad((string) $locked->id, 5, '0', STR_PAD_LEFT);

                $opBal = (float) $wallet->main_b;
                $clBal = round($opBal - $amount, 2);
                StudentTransaction::create([
                    'student_id'          => $locked->student_id,
                    'institute_id'        => $instituteId,
                    'academic_session_id' => $locked->academic_session_id,
                    'des'                 => 'Refund paid: ' . $voucherNo,
                    'credit'              => 0.00,
                    'debit'               => $amount,
                    'type'                => StudentTransaction::DEBIT,
                    'date'                => $data['paid_on'],
                    'op_bal'              => $opBal,
                    'cl_bal'              => $clBal,
                    'by_user_id'          => auth()->id(),
                ]);
                $wallet->main_b = $clBal;
                $wallet->save();

                InstituteWalletService::debitStudentRefund($locked, $voucherNo, (string) $student->name);

                $entry = JournalService::postRefundPaid(
                    $student,
                    (int) $locked->academic_session_id,
                    $amount,
                    $data['payment_mode'],
                    !empty($data['bank_account_id']) ? (int) $data['bank_account_id'] : null,
                    'refund-paid:' . $locked->id,
                    'Refund of excess fee to ' . $student->name . ' (' . $voucherNo . ')'
                );

                $locked->update([
                    'status'           => FeeRefund::PAID,
                    'payment_mode'     => $data['payment_mode'],
                    'bank_account_id'  => $data['bank_account_id'] ?? null,
                    'reference_no'     => $data['reference_no'] ?? null,
                    'paid_on'          => $data['paid_on'],
                    'paid_by'          => auth()->id(),
                    'voucher_no'       => $voucherNo,
                    'journal_entry_id' => $entry->id,
                ]);

                AuditLogService::log($instituteId, 'fee_refund', 'paid', "Refund {$voucherNo} paid: " . number_format($amount, 2), $locked);
            });
        } catch (InsufficientWalletBalanceException $e) {
            return back()->withErrors(['refund' => 'The institute wallet cannot cover this refund — ' . $e->getMessage()])->withInput();
        } catch (\DomainException $e) {
            // Only our own business-rule messages are shown; database / other errors fall through to the
            // normal error page instead of leaking query text to the screen.
            return back()->withErrors(['refund' => $e->getMessage()])->withInput();
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['refund' => 'Accounting is not set up for refunds (' . $e->getMessage() . '). Configure the Student Advance and Cash accounts first.'])->withInput();
        }

        return back()->with('success', 'Refund paid and recorded.');
    }

    public function voucher(FeeRefund $refund)
    {
        $this->scoped($refund);
        abort_unless($refund->status === FeeRefund::PAID, 404);

        $refund->load(['student', 'session', 'bankAccount']);

        return view('institute.fee.refunds.voucher', [
            'refund'    => $refund,
            'institute' => \App\Models\Institute::find($refund->institute_id),
            'modes'     => self::PAYMENT_MODES,
        ]);
    }

    /** Spreadsheet apps run a cell that starts with = + - @ as a formula; student names and references are user text. */
    private function csvSafe(?string $value): ?string
    {
        return $value !== null && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "	", "
"], true)
            ? "'" . $value
            : $value;
    }

    private function export(Request $request)
    {
        $rows = $this->query($request)->get()->map(fn(FeeRefund $r) => [
            $r->id,
            $r->voucher_no,
            $this->csvSafe($r->student?->student_uid),
            $this->csvSafe($r->student?->name),
            $this->csvSafe($r->session?->name),
            number_format((float) $r->amount, 2, '.', ''),
            $r->status,
            $r->created_at?->format('d-m-Y'),
            $r->approved_at?->format('d-m-Y'),
            $r->paid_on?->format('d-m-Y'),
            self::PAYMENT_MODES[$r->payment_mode] ?? $r->payment_mode,
            $this->csvSafe($r->reference_no),
            $this->csvSafe($r->reason),
            $this->csvSafe($r->remarks),
        ])->all();

        return $this->exportCsv(
            ['ID', 'Voucher', 'Student ID', 'Student', 'Session', 'Amount', 'Status', 'Raised on', 'Approved on', 'Paid on', 'Mode', 'Reference', 'Reason', 'Remarks'],
            $rows,
            'fee-refunds-' . now()->format('Ymd') . '.csv'
        );
    }
}
