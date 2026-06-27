<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Institute\Fee\FeeCollectionController as InstituteFeeController;
use App\Models\AcademicSession;
use App\Models\FeeInvoice;
use App\Models\PaymentModePermission;
use App\Models\Student;
use App\Models\WalletExtensionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PartnerFeeController extends Controller
{
    private function partner()
    {
        return Auth::guard('partner')->user();
    }

    public function index(Request $request)
    {
        $partner = $this->partner();
        abort_unless($partner->canCollectFee(), 403, 'Fee collection not permitted for this partner.');

        $instituteId   = $partner->institute_id;
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();
        $allSessions   = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('id')->get();

        $sessionId = $request->filled('session_id')
            ? (int) $request->session_id
            : ($activeSession?->id ?? 0);

        $dateFrom = $request->date_from ?? now()->toDateString();
        $dateTo   = $request->date_to   ?? now()->toDateString();

        $query = FeeInvoice::with(['student.stream.course', 'student.coursePart', 'student.wallets', 'session', 'items'])
            ->where('institute_id', $instituteId)
            ->where('collected_by_partner_id', $partner->id);

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }

        $query->whereDate('payment_date', '>=', $dateFrom)
              ->whereDate('payment_date', '<=', $dateTo);

        if ($request->filled('payment_mode')) {
            $query->where('payment_mode', $request->payment_mode);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_no', 'like', "%{$s}%")
                  ->orWhereHas('student', fn($sq) => $sq
                      ->where('name', 'like', "%{$s}%")
                      ->orWhere('mobile', 'like', "%{$s}%")
                      ->orWhere('student_uid', 'like', "%{$s}%"));
            });
        }

        $perPage  = in_array((int) $request->per_page, [10, 20, 50, 100], true) ? (int) $request->per_page : 20;
        $invoices = $query->orderByDesc('payment_date')->orderByDesc('id')
            ->paginate($perPage)->withQueryString();

        $pageStudentIds = $invoices->getCollection()->pluck('student_id')->unique()->filter()->values()->all();
        $totalPaidByStudent = FeeInvoice::whereIn('student_id', $pageStudentIds)
            ->where('is_cancelled', false)
            ->groupBy('student_id')
            ->selectRaw('student_id, SUM(paid_amount) as total_paid')
            ->pluck('total_paid', 'student_id');

        $statsBase = FeeInvoice::where('institute_id', $instituteId)
            ->where('collected_by_partner_id', $partner->id)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);

        $totalPaid     = (float) (clone $statsBase)->where('is_cancelled', false)->sum('paid_amount');
        $totalInvoices = (clone $statsBase)->where('is_cancelled', false)->count();
        $cashAmt       = (float) (clone $statsBase)->where('payment_mode', 'cash')->where('is_cancelled', false)->sum('paid_amount');
        $upiAmt        = (float) (clone $statsBase)->where('payment_mode', 'upi')->where('is_cancelled', false)->sum('paid_amount');
        $onlineAmt     = (float) (clone $statsBase)->where('payment_mode', 'online')->where('is_cancelled', false)->sum('paid_amount');
        $chequeAmt     = (float) (clone $statsBase)->where('payment_mode', 'cheque')->where('is_cancelled', false)->sum('paid_amount');
        $cashCount     = (clone $statsBase)->where('payment_mode', 'cash')->where('is_cancelled', false)->count();
        $upiCount      = (clone $statsBase)->where('payment_mode', 'upi')->where('is_cancelled', false)->count();
        $onlineCount   = (clone $statsBase)->where('payment_mode', 'online')->where('is_cancelled', false)->count();

        return view('partner.fee.index', compact(
            'invoices', 'allSessions', 'activeSession', 'sessionId',
            'dateFrom', 'dateTo', 'perPage',
            'totalPaid', 'totalInvoices',
            'cashAmt', 'upiAmt', 'onlineAmt', 'chequeAmt',
            'cashCount', 'upiCount', 'onlineCount',
            'totalPaidByStudent'
        ));
    }

    public function walletStatus()
    {
        $partner = $this->partner();
        $wallet  = $partner->wallet;

        $transactions = $wallet
            ? $wallet->transactions()->orderByDesc('id')->paginate(20)
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);

        $extensionRequests = WalletExtensionRequest::where('entity_type', 'channel')
            ->where('entity_id', $partner->id)
            ->orderByDesc('id')
            ->get();

        return view('partner.fee.wallet-status', compact('wallet', 'transactions', 'extensionRequests'));
    }

    public function requestExtension(Request $request)
    {
        $partner = $this->partner();
        $data = $request->validate([
            'request_type'     => 'required|in:expiry_extension,token_topup',
            'reason'           => 'required|string|max:1000',
            'requested_days'   => 'nullable|integer|min:1|max:365',
            'requested_amount' => 'nullable|numeric|min:1',
        ]);

        WalletExtensionRequest::create([
            'entity_type'      => 'channel',
            'entity_id'        => $partner->id,
            'institute_id'     => $partner->institute_id,
            'request_type'     => $data['request_type'],
            'reason'           => $data['reason'],
            'requested_days'   => $data['requested_days'] ?? null,
            'requested_amount' => $data['requested_amount'] ?? null,
            'status'           => 'pending',
        ]);

        return back()->with('extension_request_sent', 'Your request has been sent to admin.');
    }

    public function create(Request $request)
    {
        $partner = $this->partner();
        $fromAdmission = session()->has('from_admission_fee_payment');
        abort_unless($partner->canCollectFee() || $fromAdmission, 403, 'Fee collection not permitted for this partner.');

        // Wallet check — flash status so fee create blade can show popup
        $walletStatus = $partner->walletBlockStatus();
        if ($walletStatus && $walletStatus['blocked']) {
            session()->flash('wallet_blocked', $walletStatus);
        }

        if ($request->filled('student_id')) {
            $student = Student::find((int) $request->student_id);
            if ($student) {
                abort_unless(
                    $partner->canCollectFeeInSession((int) $student->academic_session_id),
                    403, 'Fee collection is not permitted for this session.'
                );
            }
        }

        return app(InstituteFeeController::class)->create($request);
    }

    public function store(Request $request)
    {
        $partner = $this->partner();
        $fromAdmission = session()->has('from_admission_fee_payment');
        abort_unless($partner->canCollectFee() || $fromAdmission, 403, 'Fee collection not permitted for this partner.');

        // Wallet check before processing fee
        $wallet = $partner->wallet;
        if ($wallet) {
            $paidAmount = collect($request->fee_items ?? [])
                ->sum(fn($item) => max(0, (float) ($item['amount'] ?? 0)));

            $walletStatus = $wallet->getBlockStatus($paidAmount);
            if ($walletStatus['blocked']) {
                return back()
                    ->withErrors(['wallet_error' => $walletStatus['reason']])
                    ->with('wallet_blocked', $walletStatus)
                    ->withInput();
            }
        }

        if ($request->filled('student_id')) {
            $student = Student::find((int) $request->student_id);
            if ($student) {
                abort_unless(
                    $partner->canCollectFeeInSession((int) $student->academic_session_id),
                    403, 'Fee collection is not permitted for this session.'
                );
            }
        }

        // Payment mode restriction — PaymentModePermission table se check karo
        if ($request->filled('payment_mode')) {
            $perm = PaymentModePermission::where('institute_id', $partner->institute_id)
                ->where('user_type', 'partner')
                ->where('user_id', $partner->id)
                ->first();
            if ($perm) {
                abort_unless(
                    in_array($request->payment_mode, $perm->allowed_modes ?? []),
                    403, 'This payment mode is not permitted for your account.'
                );
            }
        }

        // Discount limit enforcement
        if ($request->filled('discount_amount') && (float) $request->discount_amount > 0) {
            abort_unless(
                $partner->can_give_discount,
                403, 'Discount is not permitted for your account.'
            );
            $max = $partner->getMaxDiscountPercent();
            if ($max > 0 && $request->filled('total_amount') && (float) $request->total_amount > 0) {
                $discountPct = ((float) $request->discount_amount / (float) $request->total_amount) * 100;
                abort_unless(
                    $discountPct <= $max + 0.01,
                    403, "Discount exceeds the maximum allowed limit of {$max}% for your account."
                );
            }
        }

        return app(InstituteFeeController::class)->store($request);
    }

    public function searchStudent(Request $request)
    {
        $partner = $this->partner();
        abort_unless($partner->canCollectFee(), 403, 'Fee collection not permitted for this partner.');

        $sessionId = $request->filled('session_id')
            ? (int) $request->session_id
            : (int) AcademicSession::where('institute_id', $partner->institute_id)
                ->where('is_active', true)->value('id');

        if ($partner->feeScopeForSession($sessionId) === 'own') {
            return $this->searchOwnStudents($request, $partner, $sessionId);
        }

        return app(InstituteFeeController::class)->searchStudent($request);
    }

    public function receipt(Student $student, FeeInvoice $invoice)
    {
        $partner = $this->partner();
        abort_unless($partner->canCollectFee(), 403, 'Fee collection not permitted for this partner.');

        abort_if(
            $invoice->institute_id !== $partner->institute_id ||
            (int) ($invoice->collected_by_partner_id ?? 0) !== (int) $partner->id,
            403, 'This receipt is not accessible to your account.'
        );

        return app(InstituteFeeController::class)->receipt($student, $invoice);
    }

    private function searchOwnStudents(Request $request, $partner, int $sessionId = 0)
    {
        $query = $request->q ?? '';

        if (!$sessionId) {
            $sessionId = (int) AcademicSession::where('institute_id', $partner->institute_id)
                ->where('is_active', true)->value('id');
        }

        if (!$sessionId) {
            return response()->json([]);
        }

        $students = Student::where('institute_id', $partner->institute_id)
            ->where('academic_session_id', $sessionId)
            ->where('admission_source', 'channel_partner')
            ->where('admission_source_id', $partner->id)
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('mobile', 'like', "%{$query}%")
                    ->orWhere('student_uid', 'like', "%{$query}%");
            })
            ->with('stream.course')
            ->limit(10)
            ->get();

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
