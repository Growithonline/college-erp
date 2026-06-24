<?php

namespace App\Http\Controllers\Institute\Fee;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\FeeInvoice;
use App\Models\FeeInvoiceItem;
use App\Models\FeeType;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\InstituteBankAccount;
use App\Models\PaymentModePermission;
use App\Services\FeeCalculatorService;
use App\Services\StudentIdService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeCollectionController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    // ── Fee Collection List ─────────────────────────────────────────────
    public function index(Request $request)
    {
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);

        $query = FeeInvoice::with(['student.stream.course', 'session', 'items'])
            ->where('institute_id', $instituteId);

        if ($request->session_id) {
            $query->where('academic_session_id', $request->session_id);
        } elseif ($activeSession) {
            $query->where('academic_session_id', $activeSession->id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_no', 'like', "%{$s}%")
                  ->orWhereHas('student', fn($sq) =>
                      $sq->where('name', 'like', "%{$s}%")
                         ->orWhere('mobile', 'like', "%{$s}%")
                         ->orWhere('student_uid', 'like', "%{$s}%")
                  );
            });
        }

        if ($request->payment_mode) {
            $query->where('payment_mode', $request->payment_mode);
        }

        $perPage   = $request->integer('per_page', 20);
        $perPage   = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 20;

        $invoices  = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
        $sessions  = AcademicSession::where('institute_id', $instituteId)->orderBy('name')->get();
        $totalPaid = FeeInvoice::where('institute_id', $instituteId)
            ->when($request->session_id ?? $activeSession?->id, fn($q, $sid) => $q->where('academic_session_id', $sid))
            ->sum('paid_amount');

        return view('institute.fee.index', compact(
            'invoices', 'sessions', 'activeSession', 'totalPaid', 'perPage'
        ));
    }

    // ── Collect Fee Form ────────────────────────────────────────────────
    public function create(Request $request)
    {
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();

        $student      = null;
        $feeBreakup   = null;   // FeeCalculatorService result
        $alreadyPaid  = collect();
        $allFeeTypes  = FeeType::where(function ($q) use ($instituteId) {
                $q->where('institute_id', $instituteId)->orWhere('is_system', true);
            })
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        if ($request->student_id) {
            $student = Student::with(['stream.course', 'session', 'coursePart'])
                ->where('institute_id', $instituteId)
                ->findOrFail($request->student_id);

            // Student ke enrolled subjects
            $subjectIds = StudentSubject::where('student_id', $student->id)
                ->where('academic_session_id', $student->academic_session_id)
                ->pluck('subject_id')
                ->toArray();

            // FeeCalculatorService se fee calculate karo
            if ($activeSession && $student->stream) {
                $coursePart = $student->coursePart?->year_number ?? 1;
                $semester   = $activeSession->current_semester ?? 1;

                $feeBreakup = FeeCalculatorService::calculate(
                    instituteId:     $instituteId,
                    sessionId:       $activeSession->id,
                    courseId:        $student->stream->course_id,
                    coursePart:      $coursePart,
                    semester:        $semester,
                    studentType:     $student->student_type     ?? 'regular',
                    admissionSource: $student->admission_source ?? 'direct',
                    category:        $student->category         ?? 'general',
                    gender:          $student->gender           ?? 'other',
                    subjectIds:      $subjectIds,
                    courseStreamId:  $student->course_stream_id,
                    coursePartId:    $student->course_part_id
                );
            }

            // Fee items group karo — subject fees + practical fees combine
            if ($feeBreakup && !empty($feeBreakup['items'])) {
                $items = collect($feeBreakup['items']);

                $subjectFeeTotal   = $items->where('type', 'subject')->sum('amount');
                $practicalFeeTotal = $items->where('type', 'practical')->sum('amount');
                $otherItems        = $items->whereNotIn('type', ['subject', 'practical'])->values();

                $groupedItems = collect();

                // Subject Fee (All Subjects) — combined
                if ($subjectFeeTotal > 0) {
                    $groupedItems->push([
                        'type'        => 'subject_combined',
                        'fee_type_id' => null,
                        'label'       => 'Subject Fee (All Subjects)',
                        'amount'      => $subjectFeeTotal,
                    ]);
                }

                // Other fees (course, misc) — alag alag
                foreach ($otherItems as $item) {
                    $groupedItems->push($item);
                }

                // Practical Fee (All Subjects) — combined
                if ($practicalFeeTotal > 0) {
                    $groupedItems->push([
                        'type'        => 'practical_combined',
                        'fee_type_id' => null,
                        'label'       => 'Practical Fee (All Subjects)',
                        'amount'      => $practicalFeeTotal,
                    ]);
                }

                $feeBreakup['grouped_items'] = $groupedItems->values()->toArray();
            }

            // Is session mein already paid — fee_name se group karo
            $alreadyPaid = FeeInvoiceItem::whereHas('invoice', fn($q) =>
                    $q->where('student_id', $student->id)
                      ->where('academic_session_id', $student->academic_session_id)
                )
                ->selectRaw('fee_name, SUM(amount) as paid_total')
                ->groupBy('fee_name')
                ->pluck('paid_total', 'fee_name');
        }

        return view('institute.fee.create', compact(
            'activeSession', 'student', 'feeBreakup', 'alreadyPaid', 'allFeeTypes'
        ));
    }

    // ── Store Fee Invoice ───────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'student_id'   => 'required|exists:students,id',
            'payment_mode' => 'required|in:cash,online,cheque,dd,upi,neft,rtgs',
            'payment_date' => 'required|date',
            'fee_items'    => 'required|array|min:1',
        ]);

        $instituteId   = $this->instituteId();
        $student       = Student::where('institute_id', $instituteId)->findOrFail($request->student_id);
        $activeSession = AcademicSession::where('institute_id', $instituteId)
                            ->where('is_active', true)->firstOrFail();

        // Filter sirf checked items (amount > 0)
        $validItems = collect($request->fee_items)->filter(fn($item) => ($item['amount'] ?? 0) > 0);

        if ($validItems->isEmpty()) {
            return back()->withErrors(['fee_items' => 'Kam se kam ek fee item select karo aur amount daalo.'])->withInput();
        }

        $year      = StudentIdService::getYearFromSession($activeSession->name);
        $invoiceNo = StudentIdService::generateInvoiceId($instituteId, $year);

        $totalAmount = $validItems->sum(fn($item) => (float)($item['amount'] ?? 0) + (float)($item['fine'] ?? 0) + (float)($item['discount'] ?? 0));
        $totalDiscount = $validItems->sum(fn($item) => (float)($item['discount'] ?? 0));
        $discount    = $totalDiscount > 0 ? $totalDiscount : (float) ($request->discount ?? 0);
        $paidAmount  = max(0, $totalAmount - $discount);

        $invoiceId = null;

        DB::transaction(function () use (
            $request, $instituteId, $student, $activeSession,
            $invoiceNo, $totalAmount, $discount, $paidAmount, $validItems, &$invoiceId
        ) {
            $invoice = FeeInvoice::create([
                'institute_id'        => $instituteId,
                'student_id'          => $student->id,
                'academic_session_id' => $activeSession->id,
                'invoice_no'          => $invoiceNo,
                'total_amount'        => $totalAmount,
                'discount'            => $discount,
                'paid_amount'         => $paidAmount,
                'payment_mode'        => $request->payment_mode,
                'bank_account_id'     => $request->bank_account_id ?: null,
                'transaction_ref'     => $request->transaction_ref,
                'bank_name'           => $request->bank_name,
                'payment_date'        => $request->payment_date,
                'remarks'             => $request->remarks,
                'collected_by'        => auth()->guard('staff')->check()
                    ? auth()->guard('staff')->user()->name
                    : (auth()->guard('center')->check()
                        ? auth()->guard('center')->user()->name
                        : (auth()->guard('partner')->check()
                            ? auth()->guard('partner')->user()->name
                            : auth()->user()->name)),
                'collected_by_staff_id' => auth()->guard('staff')->id(),
            ]);

            $invoice->load('student');
            WalletService::onFeeCollection($invoice);

            foreach ($validItems as $item) {
                $feeType = !empty($item['fee_type_id'])
                    ? FeeType::find($item['fee_type_id'])
                    : null;

                FeeInvoiceItem::create([
                    'fee_invoice_id' => $invoice->id,
                    'fee_type_id'    => $feeType?->id ?? null,
                    'subject_id'     => !empty($item['subject_id']) ? (int) $item['subject_id'] : null,
                    'item_type'      => !empty($item['item_type']) ? (string) $item['item_type'] : null,
                    'fee_name'       => $item['fee_name'] ?? ($feeType?->name ?? 'Fee'),
                    'amount'         => (float)($item['amount'] ?? 0),
                    'fine'           => (float)($item['fine'] ?? 0),
                    'discount'       => (float)($item['discount'] ?? 0),
                    'total_fee'      => (float)($item['total_fee'] ?? $item['amount'] ?? 0),
                ]);
            }

            $invoiceId = $invoice->id;
        });

        // Admission flow se aaya hai?
        if (session('from_admission_fee_payment')) {
            session()->forget('from_admission_fee_payment');
            return redirect()->route('admissions.print-all-receipt', [
                'student' => $student->id,
                'invoice' => $invoiceId,
            ])->with('success', "Fee collected! Invoice: {$invoiceNo}");
        }

        return redirect()->route('fee.receipt', [
            'student' => $student->id,
            'invoice' => $invoiceId,
        ])->with('success', "Fee collected! Invoice: {$invoiceNo}");
    }


    // ── Cancel Invoice ───────────────────────────────────────────────────
    public function cancel(\Illuminate\Http\Request $request, Student $student, FeeInvoice $invoice)
    {
        if ($student->institute_id !== $this->instituteId()) abort(403);
        if ($invoice->student_id  !== $student->id)         abort(403);
        if ($invoice->is_cancelled) {
            return back()->with('error', 'Invoice already cancelled.');
        }

        $request->validate(['cancel_reason' => 'required|string|max:255']);

        DB::transaction(function () use ($invoice, $request) {
            $invoice->update([
                'is_cancelled'  => true,
                'cancel_reason' => $request->cancel_reason,
                'cancelled_at'  => now(),
                'cancelled_by'  => auth()->id(),
            ]);

            // Wallet reverse karo
            \App\Services\WalletService::onFeeCancel($invoice);
        });

        return back()->with('success', 'Invoice cancelled successfully.');
    }

    // ── Receipt Print ───────────────────────────────────────────────────
    public function receipt(Student $student, FeeInvoice $invoice)
    {
        if ($student->institute_id !== $this->instituteId()) abort(403);
        if ($invoice->student_id  !== $student->id)         abort(403);

        $student->load(['stream.course', 'session', 'coursePart', 'currentAcademicIdentity']);
        $invoice->load(['items.feeType', 'collectedByCenter', 'collectedByPartner']);

        $instituteId   = $this->instituteId();
        $receiptConfig = \App\Http\Controllers\Institute\Master\AdmissionFormController::getActiveConfig($instituteId, 'receipt');

        $feeItems = $invoice->items->map(fn($i) => [
            'name'      => $i->fee_name,
            'amount'    => (float) $i->amount,
            'fine'      => (float) ($i->fine ?? 0),
            'discount'  => (float) ($i->discount ?? 0),
            'total_fee' => (float) ($i->total_fee > 0 ? $i->total_fee : $i->amount),
        ])->toArray();

        $remainingDue = max(0, abs((float) (\App\Models\StudentWallet::where('student_id', $student->id)
            ->where('academic_session_id', $invoice->academic_session_id)
            ->value('main_b') ?? 0)));

        // Subjects for this session
        $studentSubjects = \App\Models\StudentSubject::with('subject')
            ->where('student_id', $student->id)
            ->where('academic_session_id', $invoice->academic_session_id)
            ->get();

        // Admission source
        $admissionSourceLabel = match($student->admission_source) {
            'center'  => 'Center',
            'partner' => 'Channel Partner',
            default   => 'Direct / Walk-in',
        };
        $admissionSourceDetail = null;
        if ($student->admission_source === 'center' && $student->admission_source_id) {
            $admissionSourceDetail = \App\Models\Center::find($student->admission_source_id)?->name;
        } elseif (in_array($student->admission_source, ['partner', 'channel_partner'], true) && $student->admission_source_id) {
            $admissionSourceDetail = \App\Models\ChannelPartner::find($student->admission_source_id)?->name;
        }

        // Fee center
        if ($invoice->collected_by_center_id) {
            $feeCenterLabel = 'Center: ' . ($invoice->collectedByCenter?->name ?? 'Unknown');
        } elseif ($invoice->collected_by_partner_id) {
            $feeCenterLabel = 'Partner: ' . ($invoice->collectedByPartner?->name ?? 'Unknown');
        } else {
            $feeCenterLabel = 'Institute';
        }

        return view('institute.fee.receipt-print', [
            'student'               => $student,
            'receipt'               => $invoice,
            'receiptConfig'         => $receiptConfig,
            'feeItems'              => $feeItems,
            'remainingDue'          => $remainingDue,
            'nextStudent'           => null,
            'studentSubjects'       => $studentSubjects,
            'admissionSourceLabel'  => $admissionSourceLabel,
            'admissionSourceDetail' => $admissionSourceDetail,
            'feeCenterLabel'        => $feeCenterLabel,
        ]);
    }

    // ── Student fee history ─────────────────────────────────────────────
    public function studentHistory(Student $student)
    {
        if ($student->institute_id !== $this->instituteId()) abort(403);
        $student->load(['stream.course', 'session']);

        $invoices  = FeeInvoice::with('items')
            ->where('student_id', $student->id)
            ->orderBy('payment_date', 'desc')
            ->get();

        // Session-wise balance (reference software jaisa)
        $sessionBalances = \App\Models\StudentWallet::where('student_id', $student->id)
            ->with('session')
            ->orderBy('academic_session_id')
            ->get();

        $totalPaid = $invoices->sum('paid_amount');

        return view('institute.fee.student-history', compact(
            'student', 'invoices', 'totalPaid', 'sessionBalances'
        ));
    }

    // ── Search student (AJAX) ───────────────────────────────────────────
    public function searchStudent(Request $request)
    {
        $q        = $request->q;
        $students = Student::where('institute_id', $this->instituteId())
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('mobile', 'like', "%{$q}%")
                      ->orWhere('student_uid', 'like', "%{$q}%");
            })
            ->with('stream.course')
            ->limit(10)->get();

        return response()->json($students->map(fn($s) => [
            'id'          => $s->id,
            'name'        => $s->name,
            'student_uid' => $s->student_uid,
            'mobile'      => $s->mobile,
            'course'      => $s->stream->course->name ?? '',
            'stream'      => $s->stream->name ?? '',
        ]));
    }
}
