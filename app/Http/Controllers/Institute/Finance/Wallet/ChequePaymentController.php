<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ChequePayment;
use App\Models\FeeInvoice;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChequePaymentController extends Controller
{
    private function instituteId(): int
    {
        return (int) auth()->user()->institute_id;
    }

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();

        $sessions  = AcademicSession::where('institute_id', $instituteId)->orderByDesc('start_date')->get();
        $sessionId = $request->input('session_id')
            ?? AcademicSession::viewSessionId($instituteId);

        $status = $request->input('status', 'pending');
        $from   = $request->input('from');
        $to     = $request->input('to');
        $search = $request->input('search');

        $query = ChequePayment::with(['invoice.student', 'session'])
            ->where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($from, fn($q) => $q->whereDate('cheque_date', '>=', $from))
            ->when($to,   fn($q) => $q->whereDate('cheque_date', '<=', $to))
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('cheque_no', 'like', "%{$search}%")
                  ->orWhere('drawee_bank', 'like', "%{$search}%")
                  ->orWhereHas('invoice.student', fn($q) => $q->where('name', 'like', "%{$search}%"));
            }));

        $cheques = $query->orderByDesc('cheque_date')->orderByDesc('id')->paginate(30)->withQueryString();

        $counts = [
            'pending' => ChequePayment::where('institute_id', $instituteId)
                ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                ->pending()->count(),
            'cleared' => ChequePayment::where('institute_id', $instituteId)
                ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                ->cleared()->count(),
            'bounced' => ChequePayment::where('institute_id', $instituteId)
                ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                ->bounced()->count(),
        ];

        return view('institute.finance.wallet.cheques.index',
            compact('sessions', 'sessionId', 'status', 'from', 'to', 'search', 'cheques', 'counts'));
    }

    public function updateStatus(Request $request, ChequePayment $cheque)
    {
        abort_if($cheque->institute_id !== $this->instituteId(), 403);
        abort_if(!$cheque->isPending(), 422, 'Only pending cheques can have their status updated.');

        $data = $request->validate([
            'status'          => 'required|in:cleared,bounced',
            'clearance_date'  => 'required_if:status,cleared|nullable|date',
            'bounce_reason'   => 'required_if:status,bounced|nullable|string|max:500',
            'remarks'         => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($cheque, $data) {
            $cheque->update([
                'status'         => $data['status'],
                'clearance_date' => $data['status'] === 'cleared' ? $data['clearance_date'] : null,
                'bounce_reason'  => $data['status'] === 'bounced' ? $data['bounce_reason'] : null,
                'remarks'        => $data['remarks'] ?? null,
                'updated_by'     => auth()->id(),
            ]);

            if ($data['status'] === 'bounced' && $cheque->fee_invoice_id) {
                $invoice = FeeInvoice::with('student')->find($cheque->fee_invoice_id);
                if ($invoice && !$invoice->is_cancelled) {
                    $invoice->update([
                        'is_cancelled'  => true,
                        'cancel_reason' => 'Cheque bounced: ' . ($data['bounce_reason'] ?? ''),
                        'cancelled_at'  => now(),
                        'cancelled_by'  => auth()->id(),
                    ]);

                    WalletService::onFeeCancel($invoice);
                }
            }
        });

        Cache::forget('pending_cheques_' . $cheque->institute_id);

        $msg = $data['status'] === 'cleared'
            ? 'Cheque marked as cleared.'
            : 'Cheque bounced — invoice cancelled and payment reversed.';

        return back()->with('success', $msg);
    }

    public function addManual(Request $request)
    {
        $instituteId = $this->instituteId();

        $data = $request->validate([
            'fee_invoice_id' => ['required', Rule::exists('fee_invoices', 'id')->where('institute_id', $instituteId)],
            'cheque_no'      => 'required|string|max:50',
            'drawee_bank'    => 'nullable|string|max:120',
            'cheque_date'    => 'required|date',
            'amount'         => 'required|numeric|min:0.01',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $invoice = FeeInvoice::where('institute_id', $instituteId)->findOrFail($data['fee_invoice_id']);

        ChequePayment::create([
            'institute_id'        => $instituteId,
            'academic_session_id' => $invoice->academic_session_id,
            'fee_invoice_id'      => $invoice->id,
            'cheque_no'           => $data['cheque_no'],
            'drawee_bank'         => $data['drawee_bank'] ?? null,
            'cheque_date'         => $data['cheque_date'],
            'amount'              => $data['amount'],
            'status'              => ChequePayment::STATUS_PENDING,
            'remarks'             => $data['remarks'] ?? null,
            'created_by'          => auth()->id(),
        ]);

        return back()->with('success', 'Cheque added manually.');
    }
}
