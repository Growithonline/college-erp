<?php

namespace App\Http\Controllers\Institute\Fee;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\CenterWallet;
use App\Models\ChannelWallet;
use App\Models\FeeInvoice;
use App\Models\FeeInvoiceItem;
use App\Models\FeeType;
use App\Models\InstituteBankAccount;
use App\Models\Library\LibraryFinePayment;
use App\Models\Library\LibraryMember;
use App\Models\PaymentModePermission;
use App\Models\Student;
use App\Services\StudentIdService;
use App\Services\WalletService;
use App\Services\AuditLogService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeCollectionController extends Controller
{
    private function feeItemKey(array $item): string
    {
        return sha1(implode('|', [
            (string) ($item['type'] ?? ''),
            (string) ($item['subject_id'] ?? ''),
            (string) ($item['fee_type_id'] ?? ''),
            trim((string) ($item['label'] ?? $item['fee_name'] ?? '')),
        ]));
    }

    private function feeTypePermissionMeta(array $feeTypeIds): array
    {
        $ids = collect($feeTypeIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return ['categories' => [], 'names' => []];
        }

        $feeTypes = FeeType::whereIn('id', $ids)->get(['id', 'name', 'category']);

        return [
            'categories' => $feeTypes->pluck('category')->filter()->values()->all(),
            'names' => $feeTypes->pluck('name')
                ->map(fn ($name) => strtolower(trim((string) $name)))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function isRestrictedItemAllowed(array $item, array $allowedIds, array $allowedCategories, array $allowedNames = []): bool
    {
        $feeTypeId = isset($item['fee_type_id']) && $item['fee_type_id'] ? (int) $item['fee_type_id'] : null;
        $itemType = strtolower(trim((string) ($item['type'] ?? $item['item_type'] ?? '')));
        $hasName = function (string $needle) use ($allowedNames): bool {
            foreach ($allowedNames as $name) {
                if (str_contains($name, $needle)) {
                    return true;
                }
            }

            return false;
        };

        if ($feeTypeId !== null) {
            return in_array($feeTypeId, $allowedIds, true);
        }

        return match ($itemType) {
            'subject', 'subject_combined' => in_array('subject_theory', $allowedCategories, true)
                || $hasName('subject fee')
                || $hasName('subject'),
            'practical', 'practical_combined' => in_array('subject_practical', $allowedCategories, true)
                || $hasName('practical fee')
                || $hasName('practical'),
            default => true,
        };
    }

    private function availableCollectableItemMap(Student $student): array
    {
        $feeState = WalletService::buildPromotionAwareFeeState($student, (int) $student->academic_session_id);
        $feeBreakup = !empty($feeState['items'])
            ? $this->filterFeeBreakupByCenterScope($this->filterFeeBreakupByStaffScope($feeState))
            : null;

        $collectItems = collect($feeBreakup['grouped_items'] ?? $feeBreakup['items'] ?? []);
        $pendingByFee = WalletService::buildPendingRows($student, (int) $student->academic_session_id)
            ->keyBy('name');

        return $collectItems->mapWithKeys(function (array $item) use ($pendingByFee) {
            $label = trim((string) ($item['label'] ?? ''));
            $pending = (float) ($pendingByFee->get($label)['pending'] ?? 0);

            return [$this->feeItemKey($item) => [
                'fee_name'                => $label,
                'fee_type_id'             => isset($item['fee_type_id']) && $item['fee_type_id'] ? (int) $item['fee_type_id'] : null,
                'subject_id'              => isset($item['subject_id']) && $item['subject_id'] ? (int) $item['subject_id'] : null,
                'item_type'               => (string) ($item['type'] ?? ''),
                'total_fee'               => (float) ($item['amount'] ?? 0),
                'pending'                 => $pending,
                'transport_allocation_id' => isset($item['transport_allocation_id']) ? (int) $item['transport_allocation_id'] : null,
            ]];
        })->all();
    }

    private function actorType(): ?string
    {
        foreach (['staff', 'center', 'partner'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }

    private function authenticatedUser()
    {
        foreach (['staff', 'center', 'partner', 'web'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return auth()->guard($guard)->user();
            }
        }

        return auth()->user();
    }

    private function instituteId(): int
    {
        $user = $this->authenticatedUser();

        abort_if(!$user || !$user->institute_id, 403, 'Institute context missing.');

        return (int) $user->institute_id;
    }

    private function actorName(): ?string
    {
        return $this->authenticatedUser()?->name;
    }

    private function actorId(): ?int
    {
        return $this->authenticatedUser()?->id;
    }

    private function currentStaff(): ?\App\Models\StaffMember
    {
        return auth()->guard('staff')->user();
    }

    private function ensureAccessibleStudent(Student $student): void
    {
        $staff = $this->currentStaff();

        if ($staff) {
            abort_if(!$staff->canAccessStudentForOperations($student), 403, 'This student is outside your access scope.');
        }
    }

    private function ensureAccessibleInvoice(FeeInvoice $invoice): void
    {
        $staff = $this->currentStaff();

        if ($staff) {
            abort_if(!$staff->canAccessFeeInvoice($invoice), 403, 'This receipt is outside your access scope.');
        }
    }

    private function enforceStaffFeeTypeScope(iterable $items): void
    {
        $staff = $this->currentStaff();

        if (!$staff || !$staff->hasRestrictedFeeCollectionTypes()) {
            return;
        }

        $allowedIds = $staff->allowedFeeCollectionTypeIds();
        $permissionMeta = $this->feeTypePermissionMeta($allowedIds);

        foreach ($items as $item) {
            abort_if(
                !$this->isRestrictedItemAllowed((array) $item, $allowedIds, $permissionMeta['categories'], $permissionMeta['names']),
                403,
                'One or more fee items are outside your access scope.'
            );
        }
    }

    private function filterFeeBreakupByStaffScope(?array $feeBreakup): ?array
    {
        $staff = $this->currentStaff();

        if (!$staff || !$staff->hasRestrictedFeeCollectionTypes() || !$feeBreakup) {
            return $feeBreakup;
        }

        $allowedIds = $staff->allowedFeeCollectionTypeIds();
        $permissionMeta = $this->feeTypePermissionMeta($allowedIds);

        $isAllowed = function (array $item) use ($allowedIds, $permissionMeta): bool {
            return $this->isRestrictedItemAllowed($item, $allowedIds, $permissionMeta['categories'], $permissionMeta['names']);
        };

        if (!empty($feeBreakup['items']) && is_array($feeBreakup['items'])) {
            $feeBreakup['items'] = array_values(array_filter($feeBreakup['items'], $isAllowed));
        }

        if (!empty($feeBreakup['grouped_items']) && is_array($feeBreakup['grouped_items'])) {
            $feeBreakup['grouped_items'] = array_values(array_filter($feeBreakup['grouped_items'], $isAllowed));
        }

        $feeBreakup['total'] = collect($feeBreakup['items'] ?? [])->sum(fn ($item) => (float) ($item['amount'] ?? 0));

        return $feeBreakup;
    }

    private function filterFeeBreakupByCenterScope(?array $feeBreakup): ?array
    {
        $center = auth()->guard('center')->user();

        if (!$center || !$center->hasRestrictedFeeCollectionTypes() || !$feeBreakup) {
            return $feeBreakup;
        }

        $allowedIds = $center->allowedFeeCollectionTypeIds();
        $permissionMeta = $this->feeTypePermissionMeta($allowedIds);

        $isItemAllowed = fn (array $item): bool => $this->isRestrictedItemAllowed($item, $allowedIds, $permissionMeta['categories'], $permissionMeta['names']);
        $isGroupedAllowed = fn (array $item): bool => $this->isRestrictedItemAllowed($item, $allowedIds, $permissionMeta['categories'], $permissionMeta['names']);

        $filteredItems = array_values(array_filter($feeBreakup['items'] ?? [], $isItemAllowed));

        $feeBreakup['items']         = $filteredItems;
        $feeBreakup['grouped_items'] = array_values(array_filter($feeBreakup['grouped_items'] ?? [], $isGroupedAllowed));
        $feeBreakup['total']         = collect($filteredItems)->sum(fn ($item) => (float) ($item['amount'] ?? 0));

        return $feeBreakup;
    }

    private function shouldLockPaymentDate(): bool
    {
        return $this->actorType() !== null;
    }

    private function allPaymentModes(): array
    {
        return [
            'cash' => 'Cash',
            'upi' => 'UPI',
            'online' => 'Online Transfer',
            'cheque' => 'Cheque',
            'dd' => 'DD',
            'neft' => 'NEFT',
            'rtgs' => 'RTGS',
        ];
    }

    private function parseAllowedModes(?string $modes): array
    {
        $parsed = array_values(array_filter(array_map('trim', explode(',', (string) $modes))));

        return $parsed ?: PaymentModePermission::defaultModes();
    }

    private function paymentPermission(): ?PaymentModePermission
    {
        $actorType = $this->actorType();
        $actorId = $this->actorId();

        if (!$actorType || !$actorId) {
            return null;
        }

        return PaymentModePermission::where('institute_id', $this->instituteId())
            ->where('user_type', $actorType)
            ->where('user_id', $actorId)
            ->first();
    }

    private function allowedPaymentModes(): array
    {
        $permission = $this->paymentPermission();

        // No PaymentModePermission record = unrestricted (all modes allowed)
        if (!$permission) {
            return array_keys($this->allPaymentModes());
        }

        return array_values(array_intersect(
            array_keys($this->allPaymentModes()),
            $permission->allowed_modes ?? []
        ));
    }

    private function allowedBankAccountIds(): ?array
    {
        $permission = $this->paymentPermission();

        // No PaymentModePermission record = unrestricted (null = all banks allowed)
        if (!$permission) {
            return null;
        }

        return array_map('intval', $permission->allowed_bank_ids ?? []);
    }

    private function allowedBankAccounts(int $instituteId, array $allowedModes, ?array $allowedBankIds = null)
    {
        // Use pre-computed value if passed, otherwise fetch it
        $allowedBankIds ??= $this->allowedBankAccountIds();

        $query = InstituteBankAccount::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->orderBy('sort_order');

        if ($allowedBankIds !== null) {
            // Explicit bank permissions: show only permitted banks without further mode filtering.
            // The bank's own allowed_payment_modes is a global default; explicit grants override it.
            $query->whereIn('id', $allowedBankIds ?: [-1]);
            return $query->get()->values();
        }

        // No explicit permissions (admin/institute): filter by each bank's own supported modes
        return $query->get()->filter(function (InstituteBankAccount $account) use ($allowedModes) {
            return !empty(array_intersect(
                $allowedModes,
                $this->parseAllowedModes($account->allowed_payment_modes)
            ));
        })->values();
    }

    private function receiptRouteName(): string
    {
        if (auth()->guard('staff')->check()) {
            return 'staff.fee.receipt';
        }

        if (auth()->guard('center')->check()) {
            return 'center.fee.receipt';
        }

        if (auth()->guard('partner')->check()) {
            return 'partner.fee.receipt';
        }

        return 'fee.receipt';
    }

    private function portalWallet(): CenterWallet|ChannelWallet|null
    {
        if ($center = auth()->guard('center')->user()) {
            return $center->wallet;
        }

        if ($partner = auth()->guard('partner')->user()) {
            return $partner->wallet;
        }

        return null;
    }

    private function refundPortalWalletForCancelledInvoice(FeeInvoice $invoice): void
    {
        $amount = (float) $invoice->paid_amount;
        if ($amount <= 0) {
            return;
        }

        if ($invoice->collected_by_center_id) {
            $wallet = CenterWallet::where('center_id', (int) $invoice->collected_by_center_id)
                ->where('institute_id', $invoice->institute_id)
                ->lockForUpdate()
                ->first();

            $wallet?->refund($amount, $invoice->id, $this->actorId());
            return;
        }

        if ($invoice->collected_by_partner_id) {
            $wallet = ChannelWallet::where('channel_partner_id', (int) $invoice->collected_by_partner_id)
                ->where('institute_id', $invoice->institute_id)
                ->lockForUpdate()
                ->first();

            $wallet?->refund($amount, $invoice->id, $this->actorId());
        }
    }

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->first();

        $dateFrom = $request->date_from ?? now()->toDateString();
        $dateTo = $request->date_to ?? now()->toDateString();

        $status = $request->get('status', 'all');
        $query = FeeInvoice::with(['student.stream.course', 'student.coursePart', 'student.wallets', 'session', 'items'])
            ->where('institute_id', $instituteId);
        if ($staff = $this->currentStaff()) {
            $staff->scopeFeeInvoices($query);
            if ($staff->hasRestrictedCourseAccess()) {
                $query->whereHas('student.stream', fn($q) => $q->whereIn('course_id', $staff->allowedCourseIds() ?: [-1]));
            }
        } elseif ($center = auth()->guard('center')->user()) {
            $query->where(fn($q) => $q
                ->where('collected_by_center_id', $center->id)
                ->orWhere(fn($sq) => $sq->whereNull('collected_by_center_id')
                    ->where('collected_by', $center->name))
            );
        } elseif ($partner = auth()->guard('partner')->user()) {
            $query->where(fn($q) => $q
                ->where('collected_by_partner_id', $partner->id)
                ->orWhere(fn($sq) => $sq->whereNull('collected_by_partner_id')
                    ->where('collected_by', $partner->name))
            );
        }

        if ($status === 'active') {
            $query->where('is_cancelled', false);
        } elseif ($status === 'cancelled') {
            $query->where('is_cancelled', true);
        }

        $sessionId = $request->session_id ?? $activeSession?->id;
        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }

        $query->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);

        if ($request->course_id) {
            $query->whereHas('student.stream', fn($q) => $q->where('course_id', $request->course_id));
        }

        if ($request->semester) {
            $query->where('semester', $request->semester);
        }

        if ($request->collected_by) {
            $query->where('collected_by', $request->collected_by);
        }

        if ($request->payment_mode) {
            $query->where('payment_mode', $request->payment_mode);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('student', fn($sq) => $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('student_uid', 'like', "%{$search}%"));
            });
        }

        $perPage = $request->integer('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

        $invoices = $query->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $statsBase = FeeInvoice::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo)
            ->when($request->course_id, fn($q) => $q->whereHas('student.stream', fn($sq) =>
                $sq->where('course_id', $request->course_id)
            ))
            ->when($request->semester, fn($q) => $q->where('semester', $request->semester))
            ->when($request->collected_by, fn($q) => $q->where('collected_by', $request->collected_by))
            ->when($request->payment_mode, fn($q) => $q->where('payment_mode', $request->payment_mode));
        if ($staff = $this->currentStaff()) {
            $staff->scopeFeeInvoices($statsBase);
            if ($staff->hasRestrictedCourseAccess()) {
                $statsBase->whereHas('student.stream', fn($q) => $q->whereIn('course_id', $staff->allowedCourseIds() ?: [-1]));
            }
        }

        if ($status === 'active') {
            $statsBase->where('is_cancelled', false);
        } elseif ($status === 'cancelled') {
            $statsBase->where('is_cancelled', true);
        }

        $totalPaid = (float) (clone $statsBase)->where('is_cancelled', false)->sum('paid_amount');
        $totalInvoices = (clone $statsBase)->count();
        $cancelledInvoices = (clone $statsBase)->where('is_cancelled', true)->count();
        $cashCount = (clone $statsBase)->where('payment_mode', 'cash')->count();
        $upiCount = (clone $statsBase)->where('payment_mode', 'upi')->count();
        $onlineCount = (clone $statsBase)->where('payment_mode', 'online')->count();
        $chequeCount = (clone $statsBase)->where('payment_mode', 'cheque')->count();
        $ddCount = (clone $statsBase)->where('payment_mode', 'dd')->count();
        $cashAmt = (float) (clone $statsBase)->where('payment_mode', 'cash')->sum('paid_amount');
        $upiAmt = (float) (clone $statsBase)->where('payment_mode', 'upi')->sum('paid_amount');
        $onlineAmt = (float) (clone $statsBase)->where('payment_mode', 'online')->sum('paid_amount');
        $chequeAmt = (float) (clone $statsBase)->where('payment_mode', 'cheque')->sum('paid_amount');
        $ddAmt = (float) (clone $statsBase)->where('payment_mode', 'dd')->sum('paid_amount');

        $sessions = AcademicSession::where('institute_id', $instituteId)->orderBy('name')->get();
        $courses = \App\Models\Course::where('institute_id', $instituteId)
            ->where('status', true)
            ->when($this->currentStaff()?->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('id', $this->currentStaff()->allowedCourseIds() ?: [-1]))
            ->orderBy('name')
            ->get();
        $collectedByListQuery = FeeInvoice::where('institute_id', $instituteId)
            ->whereNotNull('collected_by')
            ->distinct();
        if ($staff = $this->currentStaff()) {
            $staff->scopeFeeInvoices($collectedByListQuery);
        }
        $collectedByList = $collectedByListQuery->pluck('collected_by')->sort()->values();

        // Library fine payments in same date range
        $libFineQuery = LibraryFinePayment::where('institute_id', $instituteId)
            ->with(['member.student.stream.course', 'transaction.copy.book'])
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);

        if ($request->payment_mode) {
            $libFineQuery->where('payment_mode', $request->payment_mode);
        }
        if ($request->search) {
            $search = $request->search;
            $libFineQuery->where(fn($q) => $q
                ->where('receipt_no', 'like', "%{$search}%")
                ->orWhereHas('member', fn($mq) => $mq->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%"))
            );
        }

        $libFinePayments = $libFineQuery->orderBy('payment_date', 'desc')->orderBy('id', 'desc')->get();
        $libFineTotal    = (float) $libFinePayments->sum('amount');
        $libFineCount    = $libFinePayments->count();

        $libFineReceiptRoute = auth()->guard('staff')->check()
            ? 'staff.library.fines.receipt'
            : 'library.fines.receipt';

        return view('institute.fee.index', compact(
            'invoices',
            'sessions',
            'activeSession',
            'courses',
            'totalPaid',
            'totalInvoices',
            'perPage',
            'cashCount',
            'upiCount',
            'onlineCount',
            'chequeCount',
            'ddCount',
            'cashAmt',
            'upiAmt',
            'onlineAmt',
            'chequeAmt',
            'ddAmt',
            'dateFrom',
            'dateTo',
            'sessionId',
            'collectedByList'
            , 'status'
            , 'cancelledInvoices'
            , 'libFinePayments'
            , 'libFineTotal'
            , 'libFineCount'
            , 'libFineReceiptRoute'
        ));
    }

    public function create(Request $request)
    {
        $instituteId = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->first();

        $student = null;
        $feeBreakup = null;
        $alreadyPaid = collect();
        $recentInvoices = collect();
        $allFeeTypes = FeeType::where(function ($q) use ($instituteId) {
                $q->where('institute_id', $instituteId)->orWhere('is_system', true);
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($request->student_id) {
            $student = Student::with(['stream.course', 'session', 'coursePart'])
                ->where('institute_id', $instituteId)
                ->findOrFail($request->student_id);
            $this->ensureAccessibleStudent($student);

            $feeState = WalletService::buildPromotionAwareFeeState(
                $student,
                (int) $student->academic_session_id
            );

            $feeBreakup  = !empty($feeState['items'])
                ? $this->filterFeeBreakupByCenterScope($this->filterFeeBreakupByStaffScope($feeState))
                : null;
            if ($feeBreakup) {
                foreach (['items', 'grouped_items'] as $bucket) {
                    if (!empty($feeBreakup[$bucket]) && is_array($feeBreakup[$bucket])) {
                        $feeBreakup[$bucket] = array_map(function (array $item) {
                            $item['item_key'] = $this->feeItemKey($item);
                            return $item;
                        }, $feeBreakup[$bucket]);
                    }
                }
            }
            $alreadyPaid = $feeState['already_paid'] ?? collect();
            $fineByFee   = WalletService::getFineByFee($student, (int) $student->academic_session_id);

            // pendingByFee: fee name → actual pending (base + fine) from buildPendingRows
            $pendingRows   = WalletService::buildPendingRows($student, (int) $student->academic_session_id);
            $pendingByFee  = $pendingRows->pluck('pending', 'name')->toArray();

            $walletSummary = WalletService::getStudentSummary($student, (int) $student->academic_session_id);

            // Library fine data for the modal
            $libraryMember = LibraryMember::where('student_id', $student->id)->first();
            if ($libraryMember) {
                $libOutstanding = $libraryMember->transactions()
                    ->with(['copy.book'])
                    ->whereRaw('fine_amount > fine_paid')
                    ->orderBy('issued_on')
                    ->get()
                    ->each(fn($tx) => $tx->pending_fine = max(0, (float) $tx->fine_amount - (float) $tx->fine_paid));

                $libraryFineData = $libOutstanding->isNotEmpty() ? [
                    'member'        => $libraryMember,
                    'transactions'  => $libOutstanding,
                    'total_pending' => $libOutstanding->sum('pending_fine'),
                ] : null;
            }

            $recentInvoices = FeeInvoice::with(['items', 'session'])
                ->where('student_id', $student->id)
                ->orderByDesc('payment_date')
                ->orderByDesc('id');
            if ($staff = $this->currentStaff()) {
                $staff->scopeFeeInvoices($recentInvoices);
            }
            $recentInvoices = $recentInvoices->limit(8)->get();
        }

        $walletSummary   = $walletSummary   ?? ['total_due' => 0, 'balance' => 0, 'total_paid' => 0, 'total_charged' => 0];
        $fineByFee       = $fineByFee       ?? [];
        $pendingByFee    = $pendingByFee    ?? [];
        $libraryFineData = $libraryFineData ?? null;

        // Route for library fine collection depends on guard
        $libFineCollectRoute = auth()->guard('staff')->check()
            ? 'staff.library.fines.collect'
            : 'library.fines.collect';
        $libFineShowRoute = auth()->guard('staff')->check()
            ? 'staff.library.fines.show'
            : 'library.fines.show';
        $canCollectLibFine = auth()->guard('web')->check() || auth()->guard('staff')->check() || auth()->guard('library_staff')->check();
        $allowedPaymentModes = $this->allowedPaymentModes();
        $allowedBankIds = $this->allowedBankAccountIds();
        $bankAccounts = $this->allowedBankAccounts($instituteId, $allowedPaymentModes, $allowedBankIds);
        // When explicit bank permissions exist, permitted banks are available for all user-allowed modes
        $bankModeOverride = $allowedBankIds !== null ? implode(',', $allowedPaymentModes) : null;

        $staffMaxDiscount = null;
        $staffFeeAllowedTypes = null;
        $staffCollectFeeTypeIds = null;
        if (auth()->guard('staff')->check()) {
            $staffUser = auth()->guard('staff')->user();
            $staffMaxDiscount = $staffUser->max_discount_percent ?? 100;
            $perms = $staffUser->feeDiscountPermissions()->pluck('fee_type_id');
            if ($perms->isNotEmpty()) {
                $staffFeeAllowedTypes = $perms->toArray();
            }
            if ($staffUser->hasRestrictedFeeCollectionTypes()) {
                $staffCollectFeeTypeIds = $staffUser->allowedFeeCollectionTypeIds();
                $allFeeTypes = $allFeeTypes->whereIn('id', $staffCollectFeeTypeIds)->values();
            }
        } elseif (auth()->guard('center')->check()) {
            $centerUser = auth()->guard('center')->user();
            $staffMaxDiscount = $centerUser->can_give_discount
                ? (float) ($centerUser->max_discount_pct ?? 100)
                : 0;
            $discPerms = $centerUser->feeDiscountPermissions()->pluck('fee_type_id');
            if ($discPerms->isNotEmpty()) {
                $staffFeeAllowedTypes = $discPerms->toArray();
            }
            if ($centerUser->hasRestrictedFeeCollectionTypes()) {
                $staffCollectFeeTypeIds = $centerUser->allowedFeeCollectionTypeIds();
                $allFeeTypes = $allFeeTypes->whereIn('id', $staffCollectFeeTypeIds)->values();
            }
            // Build session list for session selector dropdown
            $allSessions = AcademicSession::where('institute_id', $centerUser->institute_id)
                ->orderByDesc('id')->get();
            $permsMap = $centerUser->sessionPermsMap();
            $feeSessions = $permsMap === null
                ? $allSessions
                : $allSessions->filter(fn($s) => (bool) ($permsMap[$s->id]['fee'] ?? false))->values();
            $feeSessionId = $request->filled('session_id')
                ? (int) $request->session_id
                : ($activeSession?->id ?? 0);
        } elseif (auth()->guard('partner')->check()) {
            $partnerUser = auth()->guard('partner')->user();
            $staffMaxDiscount = $partnerUser->can_give_discount
                ? (float) ($partnerUser->max_discount_pct ?? 100)
                : 0;
            // Build session list for session selector dropdown
            $allSessions = AcademicSession::where('institute_id', $partnerUser->institute_id)
                ->orderByDesc('id')->get();
            $permsMap = $partnerUser->sessionPermsMap();
            $feeSessions = $permsMap === null
                ? $allSessions
                : $allSessions->filter(fn($s) => (bool) ($permsMap[$s->id]['fee'] ?? false))->values();
            $feeSessionId = $request->filled('session_id')
                ? (int) $request->session_id
                : ($activeSession?->id ?? 0);
        }

        $feeSessions  = $feeSessions  ?? null;
        $feeSessionId = $feeSessionId ?? 0;

        $isAdmissionFeeFlow  = $student && session('from_admission_fee_payment') == $student->id;

        // If student already has a paid invoice, admission fee is done — expire the flag
        if ($isAdmissionFeeFlow && FeeInvoice::where('student_id', $student->id)->where('is_cancelled', false)->where('paid_amount', '>', 0)->exists()) {
            $isAdmissionFeeFlow = false;
            session()->forget('from_admission_fee_payment');
        }

        $canApproveAdmission = auth()->guard('web')->check()
            || (auth()->guard('staff')->check() && (bool) auth()->guard('staff')->user()?->canApproveAdmissions());

        return view('institute.fee.create', compact(
            'activeSession',
            'student',
            'feeBreakup',
            'alreadyPaid',
            'fineByFee',
            'pendingByFee',
            'walletSummary',
            'recentInvoices',
            'allFeeTypes',
            'bankAccounts',
            'allowedPaymentModes',
            'bankModeOverride',
            'staffMaxDiscount',
            'staffFeeAllowedTypes',
            'staffCollectFeeTypeIds',
            'feeSessions',
            'feeSessionId',
            'libraryFineData',
            'libFineCollectRoute',
            'libFineShowRoute',
            'canCollectLibFine',
            'isAdmissionFeeFlow',
            'canApproveAdmission'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id'   => 'required|exists:students,id',
            'payment_mode' => 'required|in:cash,online,cheque,dd,upi,neft,rtgs',
            'payment_date' => 'required_without:payment_datetime|nullable|date',
            'fee_items'    => 'required|array|min:1',
            'semester'     => 'required|integer|min:1',
        ], [
            'student_id.required'   => 'Please select a student.',
            'student_id.exists'     => 'Selected student does not exist.',
            'payment_mode.required' => 'Payment mode is required.',
            'payment_mode.in'       => 'Selected payment mode is invalid.',
            'payment_date.required_without' => 'Payment date is required.',
            'payment_date.required' => 'Payment date is required.',
            'payment_date.date'     => 'Payment date must be a valid date.',
            'fee_items.required'    => 'No fee items selected. Please select at least one fee item.',
            'fee_items.min'         => 'Please select at least one fee item to collect.',
            'semester.required'     => 'Semester is required.',
            'semester.integer'      => 'Semester must be a valid number.',
        ]);

        $instituteId = $this->instituteId();
        $student = Student::with('session')->where('institute_id', $instituteId)->findOrFail($request->student_id);
        $this->ensureAccessibleStudent($student);

        abort_if(
            $student->status === 'pending' && session('from_admission_fee_payment') != $student->id,
            422,
            "This student's admission is pending approval. Fee collection is not allowed until the admission is approved."
        );

        abort_if(
            in_array($student->status, ['passed_out', 'backlog', 'failed', 'dropped']),
            422,
            "Student status is '" . ucwords(str_replace('_', ' ', $student->status)) . "' — new fee collection is not allowed. Outstanding dues can only be cleared through the wallet."
        );

        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->firstOrFail();
        $studentSession = $student->session ?? $activeSession;
        $allowedPaymentModes = $this->allowedPaymentModes();
        $paymentDatetime = now();
        if ($request->filled('payment_datetime')) {
            try {
                $paymentDatetime = \Carbon\Carbon::parse($request->payment_datetime);
            } catch (\Exception $e) {}
        }

        $paymentDate = $this->shouldLockPaymentDate()
            ? now()->toDateString()
            : ($request->filled('payment_date')
                ? $request->payment_date
                : $paymentDatetime->toDateString());

        if (!in_array($request->payment_mode, $allowedPaymentModes, true)) {
            return back()->withErrors([
                'payment_mode' => 'Selected payment mode is not allowed for this user.',
            ])->withInput();
        }

        $allowedBankIds = $this->allowedBankAccountIds();
        $selectedBankAccount = null;
        if ($request->filled('bank_account_id')) {
            $selectedBankAccount = $this->allowedBankAccounts($instituteId, $allowedPaymentModes, $allowedBankIds)
                ->firstWhere('id', (int) $request->bank_account_id);

            if (!$selectedBankAccount) {
                return back()->withErrors([
                    'bank_account_id' => 'Selected bank account is not allowed for this user.',
                ])->withInput();
            }

            // When explicit bank permissions exist, user's allowed modes take precedence over bank's own mode list
            if ($allowedBankIds === null) {
                $bankModes = $this->parseAllowedModes($selectedBankAccount->allowed_payment_modes);
                if (!in_array($request->payment_mode, $bankModes, true)) {
                    return back()->withErrors([
                        'payment_mode' => 'Selected payment mode is not allowed for the chosen bank account.',
                    ])->withInput();
                }
            }
        } elseif ($request->payment_mode !== 'cash') {
            return back()->withErrors([
                'bank_account_id' => 'Please select an allowed bank account for this payment mode.',
            ])->withInput();
        }

        if ($request->payment_mode !== 'cash') {
            if (!$request->filled('transaction_ref')) {
                return back()->withErrors([
                    'transaction_ref' => 'Transaction Ref / UTR / Cheque No. is required for non-cash payments.',
                ])->withInput();
            }
            if (!$request->filled('payment_datetime')) {
                return back()->withErrors([
                    'payment_datetime' => 'Payment Date & Time is required for non-cash payments.',
                ])->withInput();
            }
        }

        $validItems = collect($request->fee_items)
            ->map(function ($item) {
                return [
                    'checked'                 => isset($item['checked']) ? 1 : 0,
                    'item_key'                => trim((string) ($item['item_key'] ?? '')),
                    'fee_type_id'             => $item['fee_type_id'] ?? null,
                    'subject_id'              => $item['subject_id'] ?? null,
                    'item_type'               => trim((string) ($item['item_type'] ?? '')),
                    'fee_name'                => trim((string) ($item['fee_name'] ?? '')),
                    'amount'                  => max(0, (float) ($item['amount'] ?? 0)),
                    'discount'                => max(0, (float) ($item['discount'] ?? 0)),
                    'fine'                    => max(0, (float) ($item['fine'] ?? 0)),
                    'total_fee'               => max(0, (float) ($item['total_fee'] ?? 0)),
                    'is_custom'               => !empty($item['is_custom']) ? 1 : 0,
                    'transport_allocation_id' => isset($item['transport_allocation_id']) && $item['transport_allocation_id']
                        ? (int) $item['transport_allocation_id']
                        : null,
                ];
            })
            ->filter(
                fn($item) => $item['checked'] && ($item['amount'] > 0 || $item['discount'] > 0 || $item['fine'] > 0)
            );

        if ($validItems->isEmpty()) {
            return back()->withErrors([
                'fee_items' => 'Please select at least one fee item and enter an amount.',
            ])->withInput();
        }

        $availableItems = $this->availableCollectableItemMap($student);
        $validItems = $validItems->map(function (array $item) use ($availableItems) {
            if ($item['is_custom']) {
                return $item;
            }

            $serverItem = $availableItems[$item['item_key']] ?? null;
            // Fallback for flows that don't send item_key (e.g. admission fee-payment page)
            if (!$serverItem && empty($item['item_key'])) {
                $serverItem = collect($availableItems)->firstWhere('fee_name', $item['fee_name']);
            }
            if (!$serverItem) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'fee_items' => 'One or more selected fee items are invalid or no longer available.',
                ]);
            }

            $item['fee_name']                = $serverItem['fee_name'];
            $item['fee_type_id']             = $serverItem['fee_type_id'];
            $item['subject_id']              = $serverItem['subject_id'];
            $item['item_type']               = $serverItem['item_type'];
            $item['total_fee']               = $serverItem['total_fee'];
            $item['pending_before']          = $serverItem['pending'];
            $item['transport_allocation_id'] = $serverItem['transport_allocation_id'] ?? null;

            return $item;
        });

        foreach ($validItems as $item) {
            if (!empty($item['is_custom'])) {
                continue;
            }

            $pendingBefore = max(0, (float) ($item['pending_before'] ?? 0));
            $requestedSettlement = (float) ($item['amount'] ?? 0) + (float) ($item['discount'] ?? 0);
            $allowedSettlement = $pendingBefore + (float) ($item['fine'] ?? 0);

            if ($requestedSettlement > $allowedSettlement + 0.01) {
                return back()->withErrors([
                    'fee_items' => "Collection + discount on '{$item['fee_name']}' exceeds its pending balance.",
                ])->withInput();
            }
        }

        $this->enforceStaffFeeTypeScope($validItems);

        $year = StudentIdService::getYearFromSession($studentSession->name);
        $totalAmount = (float) $validItems->sum('amount');
        $totalDiscount = (float) $validItems->sum(fn($item) => (float) ($item['discount'] ?? 0));
        $paidAmount = $totalAmount;
        $totalCleared = $totalAmount + $totalDiscount;

        // Discount limit check for staff — per fee item allowed/blocked + global % cap
        if (auth()->guard('staff')->check() && $totalDiscount > 0) {
            $staffUser   = auth()->guard('staff')->user();
            $globalLimit = $staffUser->max_discount_percent ?? 100;
            $allowedPerms = $staffUser->feeDiscountPermissions()->pluck('fee_type_id')->toArray();
            $permissionMeta = $this->feeTypePermissionMeta($allowedPerms);
            $hasPerItemConfig = count($allowedPerms) > 0;

            foreach ($validItems as $item) {
                $disc = (float) ($item['discount'] ?? 0);
                if ($disc <= 0) continue;

                $feeTypeId = isset($item['fee_type_id']) && $item['fee_type_id'] ? (int) $item['fee_type_id'] : null;

                // If per-item config exists and this fee type is not in allowed list → blocked
                if ($hasPerItemConfig && !$this->isRestrictedItemAllowed($item, $allowedPerms, $permissionMeta['categories'], $permissionMeta['names'])) {
                    return back()->withErrors([
                        'fee_items' => "Discount is not allowed on '{$item['fee_name']}'.",
                    ])->withInput();
                }

                // Global % cap check
                if ($globalLimit >= 100) continue;
                $base = (float) ($item['total_fee'] ?? 0);
                if ($base <= 0) $base = $disc + (float) ($item['amount'] ?? 0);
                if ($base > 0 && $disc > ($globalLimit / 100) * $base + 0.01) {
                    return back()->withErrors([
                        'fee_items' => "Discount on '{$item['fee_name']}' exceeds the allowed limit of {$globalLimit}%.",
                    ])->withInput();
                }
            }
        }

        // Discount limit check for center/partner
        if ($totalDiscount > 0 && (auth()->guard('center')->check() || auth()->guard('partner')->check())) {
            $portalUser = auth()->guard('center')->check()
                ? auth()->guard('center')->user()
                : auth()->guard('partner')->user();

            if (!$portalUser->can_give_discount) {
                return back()->withErrors([
                    'fee_items' => 'Discount is not permitted for your account.',
                ])->withInput();
            }

            $maxPct = (float) ($portalUser->max_discount_pct ?? 100);
            if ($maxPct < 100) {
                foreach ($validItems as $item) {
                    $disc = (float) ($item['discount'] ?? 0);
                    if ($disc <= 0) continue;
                    $base = (float) ($item['total_fee'] ?? 0);
                    if ($base <= 0) $base = $disc + (float) ($item['amount'] ?? 0);
                    if ($base > 0 && $disc > ($maxPct / 100) * $base + 0.01) {
                        return back()->withErrors([
                            'fee_items' => "Discount on '{$item['fee_name']}' exceeds the allowed limit of {$maxPct}%.",
                        ])->withInput();
                    }
                }
            }
        }

        // Per-item discount permission check for center (only specific fee types may get discount)
        if ($totalDiscount > 0 && auth()->guard('center')->check()) {
            $centerUser = auth()->guard('center')->user();
            $discAllowed = $centerUser->feeDiscountPermissions()->pluck('fee_type_id')->toArray();
            $permissionMeta = $this->feeTypePermissionMeta($discAllowed);
            if (count($discAllowed) > 0) {
                foreach ($validItems as $item) {
                    if ((float) ($item['discount'] ?? 0) > 0) {
                        if (!$this->isRestrictedItemAllowed($item, $discAllowed, $permissionMeta['categories'], $permissionMeta['names'])) {
                            return back()->withErrors([
                                'fee_items' => "Discount is not allowed on '{$item['fee_name']}'.",
                            ])->withInput();
                        }
                    }
                }
            }
        }

        // Fee type restriction for center
        if (auth()->guard('center')->check()) {
            $centerUser = auth()->guard('center')->user();
            if ($centerUser->hasRestrictedFeeCollectionTypes()) {
                $allowedTypeIds = $centerUser->allowedFeeCollectionTypeIds();
                $permissionMeta = $this->feeTypePermissionMeta($allowedTypeIds);
                foreach ($validItems as $item) {
                    if (!$this->isRestrictedItemAllowed($item, $allowedTypeIds, $permissionMeta['categories'], $permissionMeta['names'])) {
                        return back()->withErrors([
                            'fee_items' => "You are not permitted to collect '{$item['fee_name']}' fee.",
                        ])->withInput();
                    }
                }
            }
        }

        $invoiceId = null;
        $invoiceNo = null;

        try {
            DB::transaction(function () use (
                $request,
                $instituteId,
                $student,
                $activeSession,
                $year,
                $totalDiscount,
                $totalCleared,
                $paidAmount,
                $validItems,
                $selectedBankAccount,
                $paymentDate,
                $paymentDatetime,
                &$invoiceId,
                &$invoiceNo
            ) {
                $invoiceNo = StudentIdService::generateInvoiceId($instituteId, $year);
                $invoice = FeeInvoice::create([
                    'institute_id'        => $instituteId,
                    'student_id'          => $student->id,
                    'academic_session_id' => $student->academic_session_id,
                    'semester'            => $request->semester,
                    'invoice_no'          => $invoiceNo,
                    'total_amount'        => $totalCleared,
                    'discount'            => $totalDiscount,
                    'paid_amount'         => $paidAmount,
                    'payment_mode'        => $request->payment_mode,
                    'bank_account_id'     => $selectedBankAccount?->id,
                    'transaction_ref'     => $request->transaction_ref,
                    'bank_name'           => $request->bank_name,
                    'payment_date'        => $paymentDate,
                    'payment_datetime'    => $paymentDatetime,
                    'remarks'             => $request->remarks,
                    'collected_by'            => $this->actorName(),
                    'collected_by_staff_id'   => auth()->guard('staff')->id(),
                    'collected_by_center_id'  => auth()->guard('center')->id(),
                    'collected_by_partner_id' => auth()->guard('partner')->id(),
                ]);

                $invoice->load('student');

                $wallet = $this->portalWallet();
                if ($wallet && $paidAmount > 0) {
                    $wallet->consumeOrFail($paidAmount, $invoice->id, $this->actorId());
                }

                foreach ($validItems as $item) {
                    $feeType = !empty($item['fee_type_id'])
                        ? FeeType::find($item['fee_type_id'])
                        : null;

                    $fine = (float) ($item['fine'] ?? 0);

                    $assignedFee = (float) ($item['total_fee'] ?? 0);
                    if ($assignedFee <= 0) {
                        // fallback for custom fees where no assigned fee exists
                        $assignedFee = (float) ($item['amount'] ?? 0) + (float) ($item['discount'] ?? 0);
                    }

                    FeeInvoiceItem::create([
                        'fee_invoice_id' => $invoice->id,
                        'fee_type_id'    => $feeType?->id,
                        'subject_id'     => !empty($item['subject_id']) ? (int) $item['subject_id'] : null,
                        'item_type'      => $item['item_type'] ?: null,
                        'fee_name'       => $item['fee_name'] ?? ($feeType?->name ?? 'Fee'),
                        'amount'         => (float) ($item['amount'] ?? 0),
                        'discount'       => (float) ($item['discount'] ?? 0),
                        'fine'           => $fine,
                        'total_fee'      => $assignedFee,
                    ]);
                }

                WalletService::chargeCustomFeeItems($invoice, $validItems);
                WalletService::chargeFineItems($invoice, $validItems);
                WalletService::onFeeCollection($invoice);

                // Settle transport allocations collected via this invoice
                foreach ($validItems as $tItem) {
                    if (($tItem['item_type'] ?? '') === 'transport' && !empty($tItem['transport_allocation_id'])) {
                        WalletService::settleTransportFromInvoice(
                            (int) $tItem['transport_allocation_id'],
                            (float) ($tItem['amount'] ?? 0),
                            $invoice->id,
                            auth()->id()
                        );
                    }
                }

                if ($partner = auth()->guard('partner')->user()) {
                    $pct = (float) $partner->commission_percent;
                    if ($pct > 0) {
                        \App\Models\PartnerCommissionEntry::create([
                            'partner_id'         => $partner->id,
                            'fee_invoice_id'     => $invoice->id,
                            'paid_amount'        => $paidAmount,
                            'commission_percent' => $pct,
                            'commission_amount'  => round($paidAmount * $pct / 100, 2),
                        ]);
                    }
                }

                $invoiceId = $invoice->id;
            });
        } catch (DomainException $e) {
            $wallet = $this->portalWallet();
            $walletStatus = $wallet?->getBlockStatus($paidAmount);

            return back()
                ->withErrors(['wallet_error' => $e->getMessage()])
                ->with('wallet_blocked', $walletStatus)
                ->withInput();
        }

        if (session('from_admission_fee_payment') == $student->id) {
            session()->forget('from_admission_fee_payment');
            $loggedInvoice = FeeInvoice::with('items')->find($invoiceId);
            AuditLogService::log($instituteId, 'fee', 'fee_collected', 'Fee collected during admission flow.', $loggedInvoice, [
                'student_id' => $student->id,
                'invoice_no' => $invoiceNo,
            ]);

            $printRoute = match (true) {
                auth()->guard('staff')->check() => 'staff.admissions.print-all-receipt',
                auth()->guard('center')->check() => 'center.admissions.print-all-receipt',
                auth()->guard('partner')->check() => 'partner.admissions.print-all-receipt',
                default => 'admissions.print-all-receipt',
            };

            return redirect()->route($printRoute, [
                'student' => $student->id,
                'invoice' => $invoiceId,
            ])->with('success', "Fee collected! Invoice: {$invoiceNo}");
        }

        $loggedInvoice = FeeInvoice::with('items')->find($invoiceId);
        AuditLogService::log($instituteId, 'fee', 'fee_collected', 'Fee collected.', $loggedInvoice, [
            'student_id' => $student->id,
            'invoice_no' => $invoiceNo,
        ]);

        return redirect()->route($this->receiptRouteName(), [
            'student' => $student->id,
            'invoice' => $invoiceId,
        ])->with('success', "Fee collected! Invoice: {$invoiceNo}");
    }

    public function cancel(Request $request, Student $student, FeeInvoice $invoice)
    {
        $staff = $this->currentStaff();
        if ($staff && !$staff->canCancelFee()) {
            abort(403, 'Fee cancel permission required.');
        }

        if ($student->institute_id !== $this->instituteId()) {
            abort(403);
        }
        $this->ensureAccessibleStudent($student);
        if ($invoice->student_id !== $student->id) {
            abort(403);
        }
        $this->ensureAccessibleInvoice($invoice);
        if ($invoice->is_cancelled) {
            return back()->with('error', 'Invoice already cancelled.');
        }

        $request->validate(['cancel_reason' => 'required|string|max:255']);

        DB::transaction(function () use ($invoice, $request) {
            $invoice->update([
                'is_cancelled'  => true,
                'cancel_reason' => $request->cancel_reason,
                'cancelled_at'  => now(),
                'cancelled_by'  => $this->actorId(),
            ]);

            WalletService::onFeeCancel($invoice);
            $this->refundPortalWalletForCancelledInvoice($invoice);
        });
        AuditLogService::log($this->instituteId(), 'fee', 'fee_cancelled', 'Fee invoice cancelled.', $invoice, [
            'student_id' => $student->id,
            'reason' => $request->cancel_reason,
        ]);

        return back()->with('success', 'Invoice cancelled successfully.');
    }

    public function receipt(Student $student, FeeInvoice $invoice)
    {
        if ($student->institute_id !== $this->instituteId()) {
            abort(403);
        }
        $this->ensureAccessibleStudent($student);
        if ($invoice->student_id !== $student->id) {
            abort(403);
        }
        $this->ensureAccessibleInvoice($invoice);

        $student->load(['stream.course', 'session', 'currentAcademicIdentity']);
        $invoice->load(['items.feeType', 'collectedByCenter', 'collectedByPartner', 'session']);

        $instituteId = $this->instituteId();
        $receiptConfig = \App\Http\Controllers\Institute\Master\AdmissionFormController::getActiveConfig($instituteId, 'receipt');

        // Per-fee actual remaining (after ALL payments including this invoice)
        $pendingRows = WalletService::buildPendingRows($student, (int) $invoice->academic_session_id);
        $pendingByFee = $pendingRows->pluck('pending', 'name')->toArray();

        $feeItems = $invoice->items->map(fn($item) => [
            'name'      => $item->fee_name,
            'amount'    => (float) $item->amount,
            'fine'      => (float) ($item->fine ?? 0),
            'discount'  => (float) ($item->discount ?? 0),
            'total_fee' => (float) ($item->total_fee > 0 ? $item->total_fee : $item->amount),
            'actual_balance' => (float) ($pendingByFee[$item->fee_name] ?? -1), // -1 = not found
        ])->toArray();

        // Use buildPendingRows sum for accurate remaining due (not stale main_b from DB)
        $remainingDue = (float) $pendingRows->sum('pending');

        // Subjects for this student in the invoice session
        $studentSubjects = \App\Models\StudentSubject::with('subject')
            ->where('student_id', $student->id)
            ->where('academic_session_id', $invoice->academic_session_id)
            ->get();

        // Admission source label + source entity name
        $admissionSourceLabel = match($student->admission_source) {
            'center'  => 'Center',
            'partner', 'channel_partner' => 'Channel Partner',
            default   => 'Direct / Walk-in',
        };
        $admissionSourceDetail = null;
        if ($student->admission_source === 'center' && $student->admission_source_id) {
            $admissionSourceDetail = \App\Models\Center::find($student->admission_source_id)?->name;
        } elseif (in_array($student->admission_source, ['partner', 'channel_partner'], true) && $student->admission_source_id) {
            $admissionSourceDetail = \App\Models\ChannelPartner::find($student->admission_source_id)?->name;
        }

        // Fee collection center
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

    public function studentHistory(Student $student)
    {
        if ($student->institute_id !== $this->instituteId()) {
            abort(403);
        }
        $this->ensureAccessibleStudent($student);

        $student->load(['stream.course', 'session']);

        $invoices = FeeInvoice::with('items')
            ->where('student_id', $student->id)
            ->orderBy('payment_date', 'desc');
        if ($staff = $this->currentStaff()) {
            $staff->scopeFeeInvoices($invoices);
        }
        $invoices = $invoices->get();

        $sessionBalances = \App\Models\StudentWallet::where('student_id', $student->id)
            ->with('session')
            ->orderBy('academic_session_id')
            ->get();

        $totalPaid = $invoices->where('is_cancelled', false)->sum('paid_amount');

        return view('institute.fee.student-history', compact(
            'student',
            'invoices',
            'totalPaid',
            'sessionBalances'
        ));
    }

    public function searchStudent(Request $request)
    {
        $query = $request->q;
        $instituteId = $this->instituteId();

        $sessionId = $request->filled('session_id')
            ? (int) $request->session_id
            : (int) AcademicSession::where('institute_id', $instituteId)
                ->where('is_active', true)->value('id');

        if (!$sessionId) {
            return response()->json([]);
        }

        $students = Student::where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('mobile', 'like', "%{$query}%")
                    ->orWhere('student_uid', 'like', "%{$query}%")
                    ->orWhere('father_name', 'like', "%{$query}%")
                    ->orWhere('mother_name', 'like', "%{$query}%");
            })
            ->with('stream.course')
            ->limit(10);
        if ($staff = $this->currentStaff()) {
            $staff->scopeOperationalStudents($students);
        }
        $students = $students->get();

        return response()->json($students->map(fn($student) => [
            'id'          => $student->id,
            'name'        => $student->name,
            'student_uid' => $student->student_uid,
            'mobile'      => $student->mobile,
            'course'      => $student->stream->course->name ?? '',
            'stream'      => $student->stream->name ?? '',
            'father_name' => $student->father_name ?? '',
            'mother_name' => $student->mother_name ?? '',
        ]));
    }
}
