<?php

namespace App\Http\Controllers\Institute\Reports;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\ChannelPartner;
use App\Models\FeeInvoice;
use App\Models\Institute;
use App\Models\InstituteBankAccount;
use App\Models\InstituteManualIncome;
use App\Models\Library\LibraryFinePayment;
use App\Models\PaymentClaim;
use App\Models\PracticalFeeTokenEntry;
use App\Traits\ExportsTabularData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Bank-wise income report for the institute's CA: every rupee received in a date range — fee receipts,
 * library fines and other (manual) income — grouped by the bank account it went to, with a payment-mode
 * split, and with every line stating which income source it came from.
 */
class BankWiseCollectionReportController extends Controller
{
    use ExportsTabularData;

    /** Display order and labels of payment modes. */
    public const MODES = [
        'upi'    => 'UPI',
        'neft'   => 'NEFT',
        'rtgs'   => 'RTGS',
        'online' => 'Online',
        'cheque' => 'Cheque',
        'dd'     => 'DD',
        'cash'   => 'Cash',
        'other'  => 'Not recorded',
    ];

    /** Income source filter values. */
    public const SOURCES = [
        'fee'           => 'Fee receipts',
        'library_fine'  => 'Library fine',
        'manual_income' => 'Other income',
    ];

    private function instituteId(): int
    {
        $user = auth()->guard('web')->user();
        abort_if(!$user || !$user->institute_id, 403, 'Institute context missing.');

        return (int) $user->institute_id;
    }

    /** Date range (default today), optional bank / mode / source filters. */
    private function filters(Request $request): array
    {
        $parse = function (?string $value, Carbon $fallback): Carbon {
            try {
                return $value ? Carbon::parse($value)->startOfDay() : $fallback;
            } catch (\Throwable) {
                return $fallback;
            }
        };

        $from = $parse($request->input('date_from'), now()->startOfDay());
        $to   = $parse($request->input('date_to'), $from->copy());
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }
        // Keep the report (and the PDF) a sensible size
        if ($from->diffInDays($to) > 366) {
            $to = $from->copy()->addDays(366);
        }

        $mode   = (string) $request->input('payment_mode');
        $source = (string) $request->input('source');

        return [
            'date_from' => $from->toDateString(),
            'date_to'   => $to->toDateString(),
            'bank'      => (string) $request->input('bank_account_id', ''),   // '' = all, 'cash', 'none' or an id
            'mode'      => array_key_exists($mode, self::MODES) ? $mode : '',
            'source'    => array_key_exists($source, self::SOURCES) ? $source : '',
        ];
    }

    /** Everything received in the period, as one flat list of entries (one per receipt / payment). */
    private function entries(int $instituteId, array $filters): Collection
    {
        $entries = collect();
        $wants = fn(string $source) => $filters['source'] === '' || $filters['source'] === $source;

        // ── 1. Fee receipts (course fee, registration, transport, practical tokens, previous dues, ...) ──
        if ($wants('fee')) {
            $invoices = FeeInvoice::with(['student:id,name,student_uid', 'items:id,fee_invoice_id,fee_name'])
                ->where('institute_id', $instituteId)
                ->where('is_cancelled', false)
                ->where(fn($q) => $q->whereNull('approval_status')->orWhere('approval_status', FeeInvoice::STATUS_APPROVED))
                ->whereDate('payment_date', '>=', $filters['date_from'])
                ->whereDate('payment_date', '<=', $filters['date_to'])
                ->where('paid_amount', '>', 0)
                ->orderBy('payment_date')->orderBy('id')
                ->get();

            $invoiceIds = $invoices->pluck('id');
            $tokenTitles = PracticalFeeTokenEntry::with('batch:id,title')->whereIn('fee_invoice_id', $invoiceIds)->get()
                ->keyBy('fee_invoice_id')->map(fn($e) => $e->batch?->title);
            $claimInvoiceIds = PaymentClaim::whereIn('fee_invoice_id', $invoiceIds)->pluck('fee_invoice_id')->flip();
            $centers  = Center::whereIn('id', $invoices->pluck('collected_by_center_id')->filter()->unique())->pluck('name', 'id');
            $partners = ChannelPartner::whereIn('id', $invoices->pluck('collected_by_partner_id')->filter()->unique())->pluck('name', 'id');

            foreach ($invoices as $invoice) {
                $names = $invoice->items->pluck('fee_name')->filter()->unique()->values();
                $purpose = $names->take(3)->implode(', ') . ($names->count() > 3 ? ' +' . ($names->count() - 3) . ' more' : '');
                $label = 'Fee receipt';
                if ($tokenTitles->has($invoice->id)) {
                    $label = 'Practical fee token';
                    $purpose = trim(($tokenTitles[$invoice->id] ?: '') . ($purpose ? ' — ' . $purpose : ''));
                } elseif ($claimInvoiceIds->has($invoice->id)) {
                    $label = 'Fee receipt (online payment claim)';
                }

                $through = $invoice->collected_by_center_id
                    ? 'Centre: ' . ($centers[$invoice->collected_by_center_id] ?? 'Centre')
                    : ($invoice->collected_by_partner_id
                        ? 'Channel partner: ' . ($partners[$invoice->collected_by_partner_id] ?? 'Partner')
                        : ($invoice->collected_by_staff_id ? 'Staff: ' : 'Institute: ') . ($invoice->collected_by ?: '—'));

                $entries->push([
                    'date'      => $invoice->payment_date,
                    'receipt'   => $invoice->invoice_no,
                    'party'     => $invoice->student?->name,
                    'party_id'  => $invoice->student?->student_uid,
                    'source'    => 'fee',
                    'label'     => $label,
                    'purpose'   => $purpose,
                    'mode'      => $invoice->payment_mode,
                    'ref'       => $invoice->transaction_ref,
                    'through'   => $through,
                    'bank_id'   => $invoice->bank_account_id,
                    'amount'    => (float) $invoice->paid_amount,
                ]);
            }
        }

        // ── 2. Library fine payments ──
        if ($wants('library_fine')) {
            $fines = LibraryFinePayment::with('member:id,name,member_code')
                ->where('institute_id', $instituteId)
                ->whereDate('payment_date', '>=', $filters['date_from'])
                ->whereDate('payment_date', '<=', $filters['date_to'])
                ->where('amount', '>', 0)
                ->orderBy('payment_date')->orderBy('id')
                ->get();

            foreach ($fines as $fine) {
                $entries->push([
                    'date'      => $fine->payment_date instanceof Carbon ? $fine->payment_date : Carbon::parse($fine->payment_date),
                    'receipt'   => $fine->receipt_no,
                    'party'     => $fine->member?->name,
                    'party_id'  => $fine->member?->member_code,
                    'source'    => 'library_fine',
                    'label'     => 'Library fine',
                    'purpose'   => $fine->remarks ?: 'Library fine payment',
                    'mode'      => strtolower((string) $fine->payment_mode) ?: 'other',
                    'ref'       => $fine->transaction_ref,
                    'through'   => 'Library: ' . ($fine->collected_by ?: '—'),
                    'bank_id'   => $fine->bank_account_id,
                    'amount'    => (float) $fine->amount,
                ]);
            }
        }

        // ── 3. Other (manual) income — no bank or payment mode is recorded for these ──
        if ($wants('manual_income')) {
            $incomes = InstituteManualIncome::with('category:id,name')
                ->where('institute_id', $instituteId)
                ->whereDate('date', '>=', $filters['date_from'])
                ->whereDate('date', '<=', $filters['date_to'])
                ->where('amount', '>', 0)
                ->orderBy('date')->orderBy('id')
                ->get();

            foreach ($incomes as $income) {
                $entries->push([
                    'date'      => $income->date,
                    'receipt'   => $income->receipt_no,
                    'party'     => '—',
                    'party_id'  => null,
                    'source'    => 'manual_income',
                    'label'     => 'Other income — ' . ($income->category?->name ?? 'Uncategorised'),
                    'purpose'   => $income->description ?: ($income->category?->name ?? ''),
                    'mode'      => 'other',
                    'ref'       => null,
                    'through'   => 'Institute',
                    'bank_id'   => null,
                    'amount'    => (float) $income->amount,
                ]);
            }
        }

        // Normalise the payment mode and decide the group (bank account / cash / bank not recorded)
        return $entries->map(function (array $e) {
            $e['mode'] = array_key_exists((string) $e['mode'], self::MODES) ? $e['mode'] : 'other';
            $e['group'] = $e['bank_id'] ? (string) $e['bank_id'] : ($e['mode'] === 'cash' ? 'cash' : 'none');

            return $e;
        })->sortBy(fn($e) => $e['date']?->timestamp ?? 0)->values();
    }

    /** All report data for the given filters. */
    private function build(int $instituteId, array $filters): array
    {
        $entries = $this->entries($instituteId, $filters);

        if ($filters['bank'] !== '') {
            $entries = $entries->filter(fn($e) => $e['group'] === $filters['bank'])->values();
        }
        if ($filters['mode'] !== '') {
            $entries = $entries->filter(fn($e) => $e['mode'] === $filters['mode'])->values();
        }

        $banks = InstituteBankAccount::where('institute_id', $instituteId)->get()->keyBy(fn($b) => (string) $b->id);

        $groups = $entries->groupBy('group')->map(function (Collection $rows, string $key) use ($banks) {
            $bank = $banks[$key] ?? null;

            return [
                'key'     => $key,
                'title'   => $bank ? ($bank->display_label ?: $bank->bank_name)
                    : ($key === 'cash' ? 'Cash (no bank)' : 'Bank / mode not recorded'),
                'bank'    => $bank?->bank_name,
                'account' => $bank?->account_no,
                'ifsc'    => $bank?->ifsc_code,
                'rows'    => $rows->values(),
                'by_mode' => $rows->groupBy('mode')->map(fn($m) => (float) $m->sum('amount')),
                'total'   => (float) $rows->sum('amount'),
                'count'   => $rows->count(),
                'sort'    => $bank ? (int) ($bank->sort_order ?? 0) : ($key === 'cash' ? 9000 : 9500),
            ];
        })->sortBy([['sort', 'asc'], ['title', 'asc']])->values();

        $modeTotals = $entries->groupBy('mode')->map(fn($m) => (float) $m->sum('amount'));
        // Only show the mode columns that actually have money in this report
        $modes = collect(self::MODES)->filter(fn($label, $mode) => ($modeTotals[$mode] ?? 0) > 0);

        // Which income source each rupee came from
        $bySource = $entries->groupBy('label')->map(fn($rows, $label) => [
            'label' => $label,
            'count' => $rows->count(),
            'total' => (float) $rows->sum('amount'),
        ])->sortByDesc('total')->values();

        return [
            'groups'      => $groups,
            'modes'       => $modes,
            'mode_totals' => $modeTotals,
            'by_source'   => $bySource,
            'grand_total' => (float) $entries->sum('amount'),
            'grand_count' => $entries->count(),
        ];
    }

    private function viewData(Request $request): array
    {
        $instituteId = $this->instituteId();
        $filters     = $this->filters($request);

        return [
            'institute'  => Institute::find($instituteId),
            'filters'    => $filters,
            'bankList'   => InstituteBankAccount::where('institute_id', $instituteId)->orderBy('sort_order')->orderBy('bank_name')->get(),
            'allModes'   => self::MODES,
            'allSources' => self::SOURCES,
            'printedAt'  => now()->format('d M Y h:i A'),
        ] + $this->build($instituteId, $filters);
    }

    public function index(Request $request)
    {
        return view('institute.reports.bank-wise-collection', $this->viewData($request));
    }

    public function print(Request $request)
    {
        return view('institute.reports.bank-wise-collection-document', $this->viewData($request) + ['pdf' => false, 'autoPrint' => true]);
    }

    public function pdf(Request $request)
    {
        $data = $this->viewData($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('institute.reports.bank-wise-collection-document', $data + ['pdf' => true, 'autoPrint' => false]);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'dpi' => 96]);

        return $pdf->download($this->fileName($data['filters'], 'pdf'));
    }

    /** Flat sheet (one row per receipt) — easy for the CA to filter / pivot. */
    public function export(Request $request)
    {
        $data    = $this->viewData($request);
        $filters = $data['filters'];

        $headers = ['Sr', 'Date', 'Bank', 'Bank / Account No', 'Payment Mode', 'Receipt No', 'Received From', 'ID',
            'Income Source', 'Details', 'Received Through', 'UTR / Cheque / Ref No', 'Amount (Rs)'];

        $rows = [];
        $sr = 0;
        foreach ($data['groups'] as $group) {
            foreach ($group['rows'] as $e) {
                $rows[] = [
                    ++$sr,
                    $e['date']?->format('d-m-Y'),
                    $group['title'],
                    trim(($group['bank'] ? $group['bank'] . ' ' : '') . ($group['account'] ?? '')),
                    self::MODES[$e['mode']] ?? strtoupper((string) $e['mode']),
                    $e['receipt'],
                    $e['party'],
                    $e['party_id'],
                    $e['label'],
                    $e['purpose'],
                    $e['through'],
                    $e['ref'],
                    number_format((float) $e['amount'], 2, '.', ''),
                ];
            }
        }
        $rows[] = array_merge(array_fill(0, 11, ''), ['TOTAL', number_format($data['grand_total'], 2, '.', '')]);

        if ($request->input('format') === 'csv') {
            return $this->exportCsv($headers, $rows, $this->fileName($filters, 'csv'));
        }

        $title    = ($data['institute']?->name ?? 'Institute') . ' — Bank-wise Income Report';
        $subtitle = 'Period: ' . Carbon::parse($filters['date_from'])->format('d M Y') . ' to ' . Carbon::parse($filters['date_to'])->format('d M Y');

        return $this->exportSimpleExcel($headers, $rows, $this->fileName($filters, 'xlsx'), $title, $subtitle);
    }

    private function fileName(array $filters, string $ext): string
    {
        return 'bank-wise-income-' . $filters['date_from']
            . ($filters['date_to'] !== $filters['date_from'] ? '-to-' . $filters['date_to'] : '') . '.' . $ext;
    }
}
