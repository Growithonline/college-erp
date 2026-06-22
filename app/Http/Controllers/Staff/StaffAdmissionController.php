<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Institute\Admission\AdmissionController as InstituteAdmissionController;
use App\Http\Controllers\Institute\Master\AdmissionFormController;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Center;
use App\Models\FeePlan;
use App\Models\ChannelPartner;
use App\Models\CourseStream;
use App\Models\CourseType;
use App\Models\Student;
use App\Models\TransportRoute;
use App\Models\TransportRouteStop;
use App\Models\TransportVehicle;
use App\Models\TransportDriver;
use App\Http\Controllers\Institute\Master\StudentTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffAdmissionController extends Controller
{
    private function staff()
    {
        return Auth::guard('staff')->user();
    }

    private function permCheck(string $perm)
    {
        if (!$this->staff()->hasPermission($perm)) {
            abort(403, 'Permission denied.');
        }
    }

    private function ensureAdmissionsViewPermission(): void
    {
        if (!$this->staff()->canViewAdmissions()) {
            abort(403, 'Permission denied.');
        }
    }

    private function ensureStudentEditPermission(): void
    {
        if (!$this->staff()->hasPermission('student_edit') && !$this->staff()->hasPermission('admission_edit')) {
            abort(403, 'Permission denied.');
        }
    }

    private function ensureApprovalPermission(): void
    {
        if (!$this->staff()->canApproveAdmissions()) {
            abort(403, 'Permission denied.');
        }
    }

    private function ensureAccessibleStudent(Student $student): void
    {
        abort_if($student->institute_id !== $this->staff()->institute_id, 403);
        abort_if(!$this->staff()->canAccessStudentForAdmissions($student), 403, 'This student is outside your access scope.');
    }

    private function ensureAccessibleCourseSelection(?int $courseId, ?int $streamId): void
    {
        $staff = $this->staff();

        if ($streamId) {
            abort_if(!$staff->canAccessCourseStream($streamId), 403, 'Selected course is outside your access scope.');
            return;
        }

        if ($courseId) {
            abort_if(!$staff->canAccessCourse($courseId), 403, 'Selected course is outside your access scope.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmissionsViewPermission();
        $staff = $this->staff();

        $activeSession = AcademicSession::where('institute_id', $staff->institute_id)
            ->where('is_active', true)->first();

        $sessions = AcademicSession::where('institute_id', $staff->institute_id)
            ->orderByDesc('is_active')->orderByDesc('id')->get();

        $streams = CourseStream::with('course')
            ->whereHas('course', fn($q) => $q->where('institute_id', $staff->institute_id))
            ->when($staff->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('course_id', $staff->allowedCourseIds() ?: [-1]))
            ->orderBy('name')->get();

        $perPage = in_array((int) $request->per_page, [10, 25, 50, 100]) ? (int) $request->per_page : 25;

        $students = Student::with(['stream.course', 'coursePart'])
            ->where('institute_id', $staff->institute_id)
            ->when($request->filled('session_id') ? $request->session_id : $activeSession?->id,
                fn($q, $sid) => $q->where('academic_session_id', $sid))
            ->when($request->course_stream_id, fn($q, $sid) => $q->where('course_stream_id', $sid))
            ->when($request->search, fn($q) => $q->where(function($sq) use ($request) {
                $sq->where('name', 'like', "%{$request->search}%")
                   ->orWhere('mobile', 'like', "%{$request->search}%")
                   ->orWhere('student_uid', 'like', "%{$request->search}%");
            }))
            ->orderByDesc('admission_date');
        $staff->scopeAdmissionStudents($students);
        $students = $students->paginate($perPage)->withQueryString();

        return view('staff.admissions.index', compact('students', 'activeSession', 'sessions', 'streams', 'perPage'));
    }

    public function approvals(Request $request)
    {
        $this->ensureApprovalPermission();
        return app(InstituteAdmissionController::class)->approvals($request);
    }

    public function approvalShow(Student $student)
    {
        $this->ensureApprovalPermission();
        return app(InstituteAdmissionController::class)->approvalShow($student);
    }

    public function approveAdmission(Request $request, Student $student)
    {
        $this->ensureApprovalPermission();
        return app(InstituteAdmissionController::class)->approveAdmission($request, $student);
    }

    public function updateApprovalStatus(Request $request, Student $student)
    {
        $this->ensureApprovalPermission();
        return app(InstituteAdmissionController::class)->updateApprovalStatus($request, $student);
    }

    public function globalSearch(Request $request)
    {
        $this->ensureAdmissionsViewPermission();

        $staff = $this->staff();
        $sessions = AcademicSession::where('institute_id', $staff->institute_id)
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        $filters = $this->globalSearchFilters($request);
        $sessionId = $request->filled('session_id') ? (int) $request->session_id : null;

        $isInitialLoad = false;
        $students      = null;

        if ($this->hasGlobalSearchFilters($filters)) {
            $students = $this->buildGlobalSearchQuery($staff->institute_id, $filters, $sessionId)
                ->paginate(15)
                ->withQueryString();
        } else {
            $isInitialLoad = true;
            $students = \App\Models\Student::where('institute_id', $staff->institute_id)
                ->with(['stream.course', 'session', 'coursePart'])
                ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                ->orderByDesc('id');
            $staff->scopeAdmissionStudents($students);
            $students = $students->limit(50)->get();
        }

        $viewData = [
            'sessions'      => $sessions,
            'students'      => $students,
            'filters'       => $filters,
            'sessionId'     => $sessionId,
            'isInitialLoad' => $isInitialLoad,
            'layout' => 'staff.layout',
            'indexRoute' => 'staff.admissions.index',
            'searchRoute' => 'staff.students.search',
            'profileRoute' => 'staff.admissions.show',
            'walletRoute' => 'staff.fee.wallet.student',
            'historyRoute' => 'staff.fee.student-history',
            'collectFeeRoute' => 'staff.fee.create',
            'showWalletAction' => $staff->canViewFeeWallet(),
            'showHistoryAction' => $staff->canViewFeeHistory(),
            'showCollectFeeAction' => $staff->canCollectFee(),
            'listLabel' => 'My Students',
        ];

        if ($request->ajax() || $request->boolean('_ajax')) {
            return view('institute.students._global-search-results', $viewData);
        }

        return view('institute.students.global-search', $viewData);
    }

    public function quickCreate()
    {
        $this->permCheck('admission_add');
        session()->forget(['quick_admission_preview', 'quickPreviewData']);
        $staff = $this->staff();
        abort_unless($staff->canUseQuickAdmissionForm(), 403, 'Quick admission form is not permitted for your account.');
        $instituteId   = $staff->institute_id;
        $activeSession = AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->firstOrFail();

        $allSessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')->orderByDesc('id')->get();
        if (!($staff->restrict_session_access ?? false)) {
            $admissibleSessions = $allSessions;
        } else {
            $allowed = array_map('intval', $staff->allowed_session_ids ?? []);
            $admissibleSessions = $allSessions->filter(fn($s) => in_array($s->id, $allowed))->values();
        }

        $formConfig    = \App\Http\Controllers\Institute\Master\AdmissionFormController::getActiveConfig($instituteId, 'quick');
        $courses       = Course::where('institute_id', $instituteId)
            ->where('status', true)
            ->when($staff->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('id', $staff->allowedCourseIds() ?: [-1]))
            ->with(['streams','parts','type'])
            ->get();
        $courseTypeIds = $courses->pluck('course_type_id')->filter()->unique()->values();
        $courseTypes   = CourseType::whereIn('id', $courseTypeIds)->orderBy('sort_order')->orderBy('name')->get();
        $studentTypes  = StudentTypeController::getActiveTypes($instituteId);
        $centers       = Center::where('institute_id', $instituteId)->where('status', true)->get();
        $partners      = ChannelPartner::where('institute_id', $instituteId)->where('status', true)->get();
        $allModes   = ['cash', 'upi', 'online', 'cheque', 'dd', 'neft', 'rtgs'];
        $permission = \App\Models\PaymentModePermission::where('institute_id', $instituteId)
            ->where('user_type', 'staff')
            ->where('user_id', $staff->id)
            ->first();

        if ($permission) {
            $allowedPaymentModes = array_values(array_intersect($allModes, $permission->allowed_modes ?? []));
            $allowedBankIds      = array_map('intval', $permission->allowed_bank_ids ?? []);
            $bankAccounts        = \App\Models\InstituteBankAccount::where('institute_id', $instituteId)
                ->where('is_active', true)
                ->whereIn('id', $allowedBankIds ?: [-1])
                ->orderBy('sort_order')
                ->get()
                ->filter(function ($account) use ($allowedPaymentModes, $allModes) {
                    $bankModes = array_values(array_filter(array_map('trim', explode(',', (string) $account->allowed_payment_modes))));
                    $bankModes = $bankModes ?: $allModes;
                    return !empty(array_intersect($allowedPaymentModes, $bankModes));
                })->values();
        } else {
            $allowedPaymentModes = ['cash'];
            $bankAccounts        = collect();
        }

        $staffMaxDiscount     = $staff->max_discount_percent ?? 100;
        $perms                = $staff->feeDiscountPermissions()->pluck('fee_type_id');
        $staffFeeAllowedTypes = $perms->isNotEmpty() ? $perms->toArray() : null;

        $feePlans = FeePlan::with('installments')
            ->where('institute_id', $instituteId)->where('is_active', true)->orderBy('name')->get();

        return view('staff.admissions.quick-create', compact(
            'activeSession', 'admissibleSessions', 'formConfig', 'courses', 'courseTypes', 'studentTypes',
            'centers', 'partners', 'allowedPaymentModes', 'bankAccounts',
            'staffMaxDiscount', 'staffFeeAllowedTypes', 'feePlans'
        ));
    }

    public function quickStore(Request $request)
    {
        $this->permCheck('admission_add');
        $staff = $this->staff();
        abort_unless($staff->canUseQuickAdmissionForm(), 403, 'Quick admission form is not permitted for your account.');
        $this->ensureAccessibleCourseSelection(
            $request->filled('course_id') ? (int) $request->course_id : null,
            $request->filled('course_stream_id') ? (int) $request->course_stream_id : null
        );
        if ($staff->restrict_session_access ?? false) {
            $allowedIds = array_map('intval', $staff->allowed_session_ids ?? []);
            abort_unless(in_array((int) $request->session_id, $allowedIds), 403, 'You do not have access to the selected session.');
        }
        return app(InstituteAdmissionController::class)->quickStore($request);
    }

    public function create()
    {
        $this->permCheck('admission_add');
        session()->forget(['admission_preview', 'previewData']);
        $staff = $this->staff();
        abort_unless($staff->canUseFullAdmissionForm(), 403, 'Full admission form is not permitted for your account.');
        $instituteId   = $staff->institute_id;
        $activeSession = AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->firstOrFail();

        $allSessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')->orderByDesc('id')->get();
        if (!($staff->restrict_session_access ?? false)) {
            $admissibleSessions = $allSessions;
        } else {
            $allowed = array_map('intval', $staff->allowed_session_ids ?? []);
            $admissibleSessions = $allSessions->filter(fn($s) => in_array($s->id, $allowed))->values();
        }

        $formConfig    = \App\Http\Controllers\Institute\Master\AdmissionFormController::getActiveConfig($instituteId, 'admission');
        $sections      = \App\Http\Controllers\Institute\Master\AdmissionFormController::getSections('admission');
        $courses       = Course::where('institute_id', $instituteId)
            ->where('status', true)
            ->when($staff->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('id', $staff->allowedCourseIds() ?: [-1]))
            ->with(['streams','parts','type'])
            ->get();
        $courseTypeIds = $courses->pluck('course_type_id')->filter()->unique()->values();
        $courseTypes   = CourseType::whereIn('id', $courseTypeIds)->orderBy('sort_order')->orderBy('name')->get();
        $studentTypes  = StudentTypeController::getActiveTypes($instituteId);
        $centers       = Center::where('institute_id', $instituteId)->where('status', true)->get();
        $partners      = ChannelPartner::where('institute_id', $instituteId)->where('status', true)->get();

        $transportRoutes   = TransportRoute::with('stops')->where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $transportVehicles = TransportVehicle::where('institute_id', $instituteId)->where('status', true)->orderBy('vehicle_no')->get();
        $transportDrivers  = TransportDriver::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $transportStops    = TransportRouteStop::with('route:id,name')->whereHas('route', fn($q) => $q->where('institute_id', $instituteId))->where('status', true)->orderBy('sequence')->get();

        return view('staff.admissions.create', compact(
            'activeSession', 'admissibleSessions', 'formConfig', 'sections', 'courses', 'courseTypes', 'studentTypes',
            'centers', 'partners',
            'transportRoutes', 'transportVehicles', 'transportDrivers', 'transportStops'
        ));
    }

    public function store(Request $request)
    {
        $this->permCheck('admission_add');
        $staff = $this->staff();
        abort_unless($staff->canUseFullAdmissionForm(), 403, 'Full admission form is not permitted for your account.');
        $this->ensureAccessibleCourseSelection(
            $request->filled('course_id') ? (int) $request->course_id : null,
            $request->filled('course_stream_id') ? (int) $request->course_stream_id : null
        );
        if ($staff->restrict_session_access ?? false) {
            $allowedIds = array_map('intval', $staff->allowed_session_ids ?? []);
            abort_unless(in_array((int) $request->session_id, $allowedIds), 403, 'You do not have access to the selected session.');
        }
        return app(InstituteAdmissionController::class)->storePreview($request);
    }

    public function editPreview()
    {
        $this->permCheck('admission_add');

        $formData = session('admission_preview');
        if (!$formData) {
            return redirect()->route('staff.admissions.create')
                ->with('info', 'Session expired. Please fill the form again.');
        }

        $staff         = $this->staff();
        $instituteId   = $staff->institute_id;
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();
        $formConfig    = AdmissionFormController::getActiveConfig($instituteId, 'admission');
        $sections      = AdmissionFormController::getSections();
        $centers       = Center::where('institute_id', $instituteId)->where('status', true)->get();
        $partners      = ChannelPartner::where('institute_id', $instituteId)->where('status', true)->get();
        $courses       = Course::where('institute_id', $instituteId)->where('status', true)
                            ->when($staff->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('id', $staff->allowedCourseIds() ?: [-1]))
                            ->with(['streams', 'parts', 'type'])->get();
        $courseTypeIds = $courses->pluck('course_type_id')->filter()->unique()->values();
        $courseTypes   = CourseType::whereIn('id', $courseTypeIds)->orderBy('sort_order')->orderBy('name')->get();
        $studentTypes  = StudentTypeController::getActiveTypes($instituteId);

        session()->put('previewData', $formData);

        $transportRoutes   = TransportRoute::with('stops')->where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $transportVehicles = TransportVehicle::where('institute_id', $instituteId)->where('status', true)->orderBy('vehicle_no')->get();
        $transportDrivers  = TransportDriver::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $transportStops    = TransportRouteStop::with('route:id,name')->whereHas('route', fn($q) => $q->where('institute_id', $instituteId))->where('status', true)->orderBy('sequence')->get();

        return view('staff.admissions.create', compact(
            'activeSession', 'formConfig', 'sections',
            'centers', 'partners', 'courses', 'courseTypes', 'studentTypes',
            'transportRoutes', 'transportVehicles', 'transportDrivers', 'transportStops'
        ))->with('previewEdit', true);
    }

    public function confirm(Request $request)
    {
        $this->permCheck('admission_add');
        return app(InstituteAdmissionController::class)->confirmStore($request);
    }

    public function quickEditPreview()
    {
        $this->permCheck('admission_add');
        return app(InstituteAdmissionController::class)->quickEditPreview();
    }

    public function quickConfirm(Request $request)
    {
        $this->permCheck('admission_add');
        return app(InstituteAdmissionController::class)->quickConfirm($request);
    }

    public function feePayment(Student $student)
    {
        $this->permCheck('admission_add');
        $this->ensureAccessibleStudent($student);
        return app(InstituteAdmissionController::class)->feePayment($student);
    }

    public function skipFeePayment(Student $student)
    {
        $this->permCheck('admission_add');
        $this->ensureAccessibleStudent($student);
        return app(InstituteAdmissionController::class)->skipFeePayment($student);
    }

    public function getStreamSubjects(Request $request)
    {
        $this->permCheck('admission_add');
        $this->ensureAccessibleCourseSelection(null, $request->filled('course_stream_id') ? (int) $request->course_stream_id : null);
        return app(InstituteAdmissionController::class)->getStreamSubjects($request);
    }

    public function getStreamSeats(Request $request)
    {
        $this->permCheck('admission_add');
        $this->ensureAccessibleCourseSelection(null, $request->filled('course_stream_id') ? (int) $request->course_stream_id : null);
        return app(InstituteAdmissionController::class)->getStreamSeats($request);
    }

    public function feePreview(Request $request)
    {
        $this->permCheck('admission_add');
        return app(InstituteAdmissionController::class)->feePreview($request);
    }

    public function quickSuccess(Student $student)
    {
        $this->permCheck('admission_add');
        $this->ensureAccessibleStudent($student);
        return app(InstituteAdmissionController::class)->quickSuccess($student);
    }

    public function printForm(Student $student)
    {
        $this->permCheck('admission_add');
        $this->ensureAccessibleStudent($student);
        return app(InstituteAdmissionController::class)->printForm($student);
    }

    public function printAll(Student $student, \App\Models\FeeInvoice $invoice = null)
    {
        $this->permCheck('admission_add');
        $this->ensureAccessibleStudent($student);
        return app(InstituteAdmissionController::class)->printAll($student, $invoice);
    }

    public function show(Student $student, \Illuminate\Http\Request $request)
    {
        $this->ensureAdmissionsViewPermission();
        $this->ensureAccessibleStudent($student);
        return app(InstituteAdmissionController::class)->show($student, $request);
    }

    public function edit(Student $student)
    {
        $this->ensureStudentEditPermission();
        $this->ensureAccessibleStudent($student);
        return app(InstituteAdmissionController::class)->edit($student);
    }

    public function update(Request $request, Student $student)
    {
        $this->ensureStudentEditPermission();
        $this->ensureAccessibleStudent($student);
        $this->ensureAccessibleCourseSelection(
            $request->filled('course_id') ? (int) $request->course_id : null,
            $request->filled('course_stream_id') ? (int) $request->course_stream_id : null
        );
        return app(InstituteAdmissionController::class)->update($request, $student);
    }

    // ── Admission Approve / Status Toggle ────────────────────────────
    public function toggleStatus(Request $request, Student $student)
    {
        $this->permCheck('admission_approve');
        abort_if($student->institute_id !== $this->staff()->institute_id, 403);
        abort_if(!$this->staff()->canAccessStudentForAdmissions($student), 403, 'This student is outside your access scope.');

        $allowed = ['pending', 'active', 'inactive', 'detained', 'cancelled'];
        $newStatus = $request->input('status');
        if (!in_array($newStatus, $allowed, true)) {
            return back()->with('error', 'Invalid status.');
        }

        $updateData = ['status' => $newStatus];
        if ($newStatus === 'active') {
            $updateData['approved_by_staff_id'] = $this->staff()->id;
            $updateData['approved_by_name'] = $this->staff()->name;
            $updateData['approved_at'] = now();
        } elseif ($newStatus === 'pending') {
            $updateData['approved_by_staff_id'] = null;
            $updateData['approved_by_name'] = null;
            $updateData['approved_at'] = null;
        }

        $student->update($updateData);

        return back()->with('success', $student->name . '\'s status has been updated to "' . $newStatus . '".');
    }

    // ── Admission Delete ─────────────────────────────────────────────
    public function destroy(Student $student)
    {
        $this->permCheck('admission_delete');
        abort_if($student->institute_id !== $this->staff()->institute_id, 403);
        abort_if(!$this->staff()->canAccessStudentForAdmissions($student), 403, 'This student is outside your access scope.');

        $name = $student->name;
        $student->delete();

        return redirect()->route('staff.admissions.index')
            ->with('success', $name . ' ko delete kar diya gaya.');
    }

    // ── Student Promote ───────────────────────────────────────────────
    public function promoteIndex(Request $request)
    {
        $this->permCheck('student_promote');
        $controller = app(\App\Http\Controllers\Institute\Admission\StudentPromoteController::class);
        $response   = $controller->index($request);

        if ($response instanceof \Illuminate\View\View) {
            $response->with('layout', 'staff.layout')
                     ->with('promoteBase', '/staff/admissions/promote');
        }

        return $response;
    }

    public function promotePreview(Request $request)
    {
        $this->permCheck('student_promote');
        return app(\App\Http\Controllers\Institute\Admission\StudentPromoteController::class)->preview($request);
    }

    public function promoteStore(Request $request)
    {
        $this->permCheck('student_promote');
        return app(\App\Http\Controllers\Institute\Admission\StudentPromoteController::class)->promote($request);
    }

    public function promoteBulk(Request $request)
    {
        $this->permCheck('student_promote');
        return app(\App\Http\Controllers\Institute\Admission\StudentPromoteController::class)->bulkPromote($request);
    }

    public function promoteParts(Request $request)
    {
        $this->permCheck('student_promote');
        return app(\App\Http\Controllers\Institute\Admission\StudentPromoteController::class)->getParts($request);
    }

    private function buildGlobalSearchQuery(int $instituteId, array $filters, ?int $sessionId = null)
    {
        $query = Student::where('institute_id', $instituteId)
            ->with(['stream.course', 'session', 'coursePart'])
            ->orderBy('name');

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }

        $this->staff()->scopeAdmissionStudents($query);
        $this->applyGlobalSearchFilters($query, $filters);

        return $query;
    }

    private function applyGlobalSearch($query, string $searchText): void
    {
        $query->where(function ($builder) use ($searchText) {
            $builder->where('name', 'like', "%{$searchText}%")
                ->orWhere('father_name', 'like', "%{$searchText}%")
                ->orWhere('mother_name', 'like', "%{$searchText}%")
                ->orWhere('mobile', 'like', "%{$searchText}%")
                ->orWhere('email', 'like', "%{$searchText}%")
                ->orWhere('student_uid', 'like', "%{$searchText}%")
                ->orWhere('roll_no', 'like', "%{$searchText}%")
                ->orWhere('enrollment_no', 'like', "%{$searchText}%")
                ->orWhereHas('academicIdentities', function ($identityQuery) use ($searchText) {
                    $identityQuery->where(function ($identityBuilder) use ($searchText) {
                        $identityBuilder->where('roll_no', 'like', "%{$searchText}%")
                            ->orWhere('form_no', 'like', "%{$searchText}%")
                            ->orWhere('roll_no_snapshot', 'like', "%{$searchText}%")
                            ->orWhere('enrollment_no_snapshot', 'like', "%{$searchText}%");
                    });
                });
        });
    }

    private function globalSearchFilters(Request $request): array
    {
        return [
            'student_name' => trim((string) $request->input('student_name', '')),
            'father_name' => trim((string) $request->input('father_name', '')),
            'mother_name' => trim((string) $request->input('mother_name', '')),
            'mobile' => trim((string) $request->input('mobile', '')),
            'email' => trim((string) $request->input('email', '')),
            'student_id' => trim((string) $request->input('student_id', '')),
            'roll_no' => trim((string) $request->input('roll_no', '')),
            'enrollment_no' => trim((string) $request->input('enrollment_no', '')),
        ];
    }

    private function hasGlobalSearchFilters(array $filters): bool
    {
        foreach ($filters as $value) {
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    private function applyGlobalSearchFilters($query, array $filters): void
    {
        if ($filters['student_name'] !== '') {
            $words = array_filter(preg_split('/\s+/', trim($filters['student_name'])));
            foreach ($words as $word) {
                $query->where('name', 'like', '%' . $word . '%');
            }
        }

        if ($filters['father_name'] !== '') {
            $words = array_filter(preg_split('/\s+/', trim($filters['father_name'])));
            foreach ($words as $word) {
                $query->where('father_name', 'like', '%' . $word . '%');
            }
        }

        if ($filters['mother_name'] !== '') {
            $words = array_filter(preg_split('/\s+/', trim($filters['mother_name'])));
            foreach ($words as $word) {
                $query->where('mother_name', 'like', '%' . $word . '%');
            }
        }

        if ($filters['mobile'] !== '') {
            $query->where('mobile', 'like', '%' . $filters['mobile'] . '%');
        }

        if ($filters['email'] !== '') {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }

        if ($filters['student_id'] !== '') {
            $query->where('student_uid', 'like', '%' . $filters['student_id'] . '%');
        }

        if ($filters['roll_no'] !== '') {
            $searchText = $filters['roll_no'];
            $query->where(function ($builder) use ($searchText) {
                $builder->where('roll_no', 'like', "%{$searchText}%")
                    ->orWhereHas('academicIdentities', function ($identityQuery) use ($searchText) {
                        $identityQuery->where(function ($identityBuilder) use ($searchText) {
                            $identityBuilder->where('roll_no', 'like', "%{$searchText}%")
                                ->orWhere('roll_no_snapshot', 'like', "%{$searchText}%");
                        });
                    });
            });
        }

        if ($filters['enrollment_no'] !== '') {
            $searchText = $filters['enrollment_no'];
            $query->where(function ($builder) use ($searchText) {
                $builder->where('enrollment_no', 'like', "%{$searchText}%")
                    ->orWhereHas('academicIdentities', function ($identityQuery) use ($searchText) {
                        $identityQuery->where('enrollment_no_snapshot', 'like', "%{$searchText}%");
                    });
            });
        }
    }
}
