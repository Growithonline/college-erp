<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Institute\Admission\AdmissionController as InstituteAdmissionController;
use App\Http\Controllers\Institute\Master\StudentTypeController;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseStream;
use App\Models\InstituteBankAccount;
use App\Models\PaymentModePermission;
use App\Models\Student;
use App\Models\TransportRoute;
use App\Models\TransportRouteStop;
use App\Models\TransportVehicle;
use App\Models\TransportDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PartnerStudentController extends Controller
{
    private function partner()
    {
        return Auth::guard('partner')->user();
    }

    private function permissionCheck(string $permission): void
    {
        abort_unless($this->partner()->hasPermission($permission), 403, 'You do not have permission to perform this action.');
    }

    // ── Student List ───────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->permissionCheck('student_view');
        $partner = $this->partner();

        $activeSession = AcademicSession::where('institute_id', $partner->institute_id)
            ->where('is_active', true)->first();

        $sessionId = $request->session_id ?? $activeSession?->id;

        $query = Student::with(['stream.course', 'coursePart'])
            ->where('institute_id', $partner->institute_id);

        if ($sessionId) {
            abort_unless($partner->canViewStudentsInSession((int) $sessionId), 403, 'You do not have permission to view students in this session.');
        }

        // Per-session student scope, falls back to global
        $isOwnScope = $sessionId
            ? $partner->studentScopeForSession((int) $sessionId) === 'own'
            : $partner->isStudentScopeOwn();

        if ($isOwnScope) {
            $query->where('admission_source', 'channel_partner')
                  ->where('admission_source_id', $partner->id);
        }

        $query->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
              ->when($request->search, fn($q) => $q->where(function ($sq) use ($request) {
                  $sq->where('name', 'like', "%{$request->search}%")
                     ->orWhere('mobile', 'like', "%{$request->search}%")
                     ->orWhere('student_uid', 'like', "%{$request->search}%");
              }))
              ->orderByDesc('admission_date');

        $students = $query->paginate(20)->withQueryString();

        return view('partner.students.index', compact('students', 'activeSession', 'sessionId'));
    }

    // ── Global Search ──────────────────────────────────────────────────────

    public function globalSearch(Request $request)
    {
        $this->permissionCheck('student_view');

        $partner  = $this->partner();
        $sessions = AcademicSession::where('institute_id', $partner->institute_id)
            ->orderByDesc('is_active')->orderByDesc('id')->get();

        $filters   = $this->globalSearchFilters($request);
        $sessionId = $request->filled('session_id') ? (int) $request->session_id : null;

        $isInitialLoad = false;
        $students      = null;

        if ($this->hasGlobalSearchFilters($filters)) {
            $students = $this->buildGlobalSearchQuery($partner, $filters, $sessionId)
                ->paginate(12)->withQueryString();
        } else {
            $isInitialLoad = true;
            $baseQuery = Student::where('institute_id', $partner->institute_id)
                ->with(['stream.course', 'session', 'coursePart'])
                ->orderBy('name');
            $initOwnScope = $sessionId
                ? $partner->studentScopeForSession((int) $sessionId) === 'own'
                : $partner->isStudentScopeOwn();
            if ($initOwnScope) {
                $baseQuery->where('admission_source', 'channel_partner')
                          ->where('admission_source_id', $partner->id);
            }
            if ($sessionId) {
                $baseQuery->where('academic_session_id', $sessionId);
            }
            $students = $baseQuery->limit(30)->get();
        }

        $viewData = [
            'sessions'             => $sessions,
            'students'             => $students,
            'filters'              => $filters,
            'sessionId'            => $sessionId,
            'isInitialLoad'        => $isInitialLoad,
            'layout'               => 'partner.layout',
            'indexRoute'           => 'partner.students.index',
            'searchRoute'          => 'partner.students.search',
            'profileRoute'         => 'partner.students.show',
            'walletRoute'          => null,
            'historyRoute'         => null,
            'collectFeeRoute'      => 'partner.fee.create',
            'showWalletAction'     => false,
            'showHistoryAction'    => false,
            'showCollectFeeAction' => $partner->canCollectFee(),
            'listLabel'            => 'My Students',
        ];

        if ($request->ajax() || $request->boolean('_ajax')) {
            return view('institute.students._global-search-results', $viewData);
        }

        return view('institute.students.global-search', $viewData);
    }

    // ── Student Profile ────────────────────────────────────────────────────

    public function show(Student $student)
    {
        $this->permissionCheck('student_view');
        $partner = $this->partner();

        abort_if((int) $student->institute_id !== (int) $partner->institute_id, 403, 'Student not found.');

        abort_unless($partner->canViewStudentsInSession((int) $student->academic_session_id), 403, 'You do not have permission to view students in this session.');

        if ($partner->studentScopeForSession((int) $student->academic_session_id) === 'own') {
            abort_if(
                $student->admission_source !== 'channel_partner' ||
                (int) $student->admission_source_id !== (int) $partner->id,
                403, 'Student not found.'
            );
        }

        $student->load(['stream.course', 'coursePart', 'educationDetails']);

        return view('partner.students.show', compact('student', 'partner'));
    }

    // ── Quick Admission ────────────────────────────────────────────────────

    public function quickCreate()
    {
        $this->permissionCheck('admission_add');
        $partner = $this->partner();
        abort_unless($partner->canUseQuickAdmissionForm(), 403, 'Quick admission form not permitted for this partner.');

        $instituteId   = $partner->institute_id;
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->firstOrFail();

        abort_unless($partner->canAdmitInSession($activeSession->id), 403, 'Admission in the current session is not permitted for this partner.');

        $formConfig   = \App\Http\Controllers\Institute\Master\AdmissionFormController::getActiveConfig($instituteId, 'quick');
        $studentTypes = StudentTypeController::getActiveTypes($instituteId);

        $courses = Course::where('institute_id', $instituteId)->where('status', true)
            ->with(['streams', 'parts', 'type'])->get();

        if ($partner->allowed_courses !== null) {
            $courses = $courses->whereIn('id', $partner->allowed_courses)->values();
        }

        $courseTypes  = $courses->pluck('type')->filter()->unique('id')->sortBy('sort_order')->values();

        $centers  = collect();
        $partners = collect([$partner]);

        $allModes   = ['cash', 'upi', 'online', 'cheque', 'dd', 'neft', 'rtgs'];
        $permission = PaymentModePermission::where('institute_id', $instituteId)
            ->where('user_type', 'partner')
            ->where('user_id', $partner->id)
            ->first();

        if ($permission) {
            $allowedPaymentModes = array_values(array_intersect($allModes, $permission->allowed_modes ?? []));
            $allowedBankIds      = array_map('intval', $permission->allowed_bank_ids ?? []);
            $bankAccounts        = InstituteBankAccount::where('institute_id', $instituteId)
                ->where('is_active', true)
                ->whereIn('id', $allowedBankIds ?: [-1])
                ->orderBy('sort_order')->get();
        } else {
            $allowedPaymentModes = $allModes;
            $bankAccounts        = InstituteBankAccount::where('institute_id', $instituteId)
                ->where('is_active', true)->orderBy('sort_order')->get();
        }

        return view('partner.admissions.quick-create', compact(
            'activeSession', 'formConfig', 'courses', 'courseTypes', 'studentTypes',
            'centers', 'partners', 'partner', 'allowedPaymentModes', 'bankAccounts'
        ));
    }

    public function quickStore(Request $request)
    {
        $this->permissionCheck('admission_add');
        $partner = $this->partner();
        abort_unless($partner->canUseQuickAdmissionForm(), 403, 'Quick admission form not permitted for this partner.');

        $sessionId = $request->filled('academic_session_id')
            ? (int) $request->academic_session_id
            : (int) AcademicSession::where('institute_id', $partner->institute_id)->where('is_active', true)->value('id');
        abort_unless(
            $partner->canAdmitInSession($sessionId),
            403, 'Admission in this session is not permitted for your account.'
        );

        if ($partner->allowed_courses !== null && $request->filled('course_stream_id')) {
            $courseId = CourseStream::where('id', $request->course_stream_id)->value('course_id');
            abort_unless(
                $courseId && $partner->isAllowedCourse((int) $courseId),
                403, 'Admission for this course is not permitted for your account.'
            );
        }

        $request->merge([
            'admission_source'    => 'channel_partner',
            'admission_source_id' => $partner->id,
        ]);

        return app(InstituteAdmissionController::class)->quickStore($request);
    }

    // ── Full Admission ─────────────────────────────────────────────────────

    public function create()
    {
        $this->permissionCheck('admission_add');
        $partner = $this->partner();
        abort_unless($partner->canUseFullAdmissionForm(), 403, 'Full admission form not permitted for this partner.');

        $instituteId   = $partner->institute_id;
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->firstOrFail();

        abort_unless($partner->canAdmitInSession($activeSession->id), 403, 'Admission in the current session is not permitted for this partner.');

        $formConfig  = \App\Http\Controllers\Institute\Master\AdmissionFormController::getActiveConfig($instituteId, 'admission');
        $sections    = \App\Http\Controllers\Institute\Master\AdmissionFormController::getSections('admission');
        $studentTypes = StudentTypeController::getActiveTypes($instituteId);

        $courses = Course::where('institute_id', $instituteId)->where('status', true)
            ->with(['streams', 'parts', 'type'])->get();

        if ($partner->allowed_courses !== null) {
            $courses = $courses->whereIn('id', $partner->allowed_courses)->values();
        }

        $courseTypes = $courses->pluck('type')->filter()->unique('id')->sortBy('sort_order')->values();

        $centers  = collect();
        $partners = collect([$partner]);

        $transportRoutes   = TransportRoute::with('stops')->where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $transportVehicles = TransportVehicle::where('institute_id', $instituteId)->where('status', true)->orderBy('vehicle_no')->get();
        $transportDrivers  = TransportDriver::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $transportStops    = TransportRouteStop::with('route:id,name')->whereHas('route', fn($q) => $q->where('institute_id', $instituteId))->where('status', true)->orderBy('sequence')->get();

        return view('partner.admissions.create', compact(
            'activeSession', 'formConfig', 'sections', 'courses', 'courseTypes', 'studentTypes',
            'centers', 'partners', 'partner',
            'transportRoutes', 'transportVehicles', 'transportDrivers', 'transportStops'
        ));
    }

    public function store(Request $request)
    {
        $this->permissionCheck('admission_add');
        $partner = $this->partner();
        abort_unless($partner->canUseFullAdmissionForm(), 403, 'Full admission form not permitted for this partner.');

        $sessionId = $request->filled('academic_session_id')
            ? (int) $request->academic_session_id
            : (int) AcademicSession::where('institute_id', $partner->institute_id)->where('is_active', true)->value('id');
        abort_unless($partner->canAdmitInSession($sessionId), 403, 'Admission in this session is not permitted for your account.');

        if ($partner->allowed_courses !== null && $request->filled('course_stream_id')) {
            $courseId = CourseStream::where('id', $request->course_stream_id)->value('course_id');
            abort_unless(
                $courseId && $partner->isAllowedCourse((int) $courseId),
                403, 'Admission for this course is not permitted for your account.'
            );
        }

        $request->merge([
            'admission_source'    => 'channel_partner',
            'admission_source_id' => $partner->id,
        ]);

        return app(InstituteAdmissionController::class)->storePreview($request);
    }

    // ── Delegated admission actions ────────────────────────────────────────

    public function editPreview()
    {
        $this->permissionCheck('admission_add');
        return app(InstituteAdmissionController::class)->editPreview();
    }

    public function confirm(Request $request)
    {
        $this->permissionCheck('admission_add');
        return app(InstituteAdmissionController::class)->confirmStore($request);
    }

    public function quickEditPreview()
    {
        $this->permissionCheck('admission_add');
        return app(InstituteAdmissionController::class)->quickEditPreview();
    }

    public function quickConfirm(Request $request)
    {
        $this->permissionCheck('admission_add');
        return app(InstituteAdmissionController::class)->quickConfirm($request);
    }

    public function getStreamSubjects(Request $request)
    {
        $this->permissionCheck('admission_add');
        return app(InstituteAdmissionController::class)->getStreamSubjects($request);
    }

    public function getStreamSeats(Request $request)
    {
        $this->permissionCheck('admission_add');
        return app(InstituteAdmissionController::class)->getStreamSeats($request);
    }

    // ── Global Search helpers ──────────────────────────────────────────────

    private function globalSearchFilters(Request $request): array
    {
        return [
            'student_name'  => trim((string) $request->input('student_name', '')),
            'father_name'   => trim((string) $request->input('father_name', '')),
            'mobile'        => trim((string) $request->input('mobile', '')),
            'email'         => trim((string) $request->input('email', '')),
            'student_id'    => trim((string) $request->input('student_id', '')),
            'enrollment_no' => trim((string) $request->input('enrollment_no', '')),
        ];
    }

    private function hasGlobalSearchFilters(array $filters): bool
    {
        foreach ($filters as $value) {
            if ($value !== '') return true;
        }
        return false;
    }

    private function buildGlobalSearchQuery($partner, array $filters, ?int $sessionId = null)
    {
        $query = Student::where('institute_id', $partner->institute_id)
            ->with(['stream.course', 'session', 'coursePart'])
            ->orderBy('name');

        $searchOwnScope = $sessionId
            ? $partner->studentScopeForSession((int) $sessionId) === 'own'
            : $partner->isStudentScopeOwn();

        if ($searchOwnScope) {
            $query->where('admission_source', 'channel_partner')
                  ->where('admission_source_id', $partner->id);
        }

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }

        if ($filters['student_name'] !== '') {
            $query->where('name', 'like', '%' . $filters['student_name'] . '%');
        }
        if ($filters['father_name'] !== '') {
            $query->where('father_name', 'like', '%' . $filters['father_name'] . '%');
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
        if ($filters['enrollment_no'] !== '') {
            $query->where('enrollment_no', 'like', '%' . $filters['enrollment_no'] . '%');
        }

        return $query;
    }

    public function feePreview(Request $request)
    {
        $this->permissionCheck('admission_add');
        return app(InstituteAdmissionController::class)->feePreview($request);
    }

    public function feePayment(Student $student)
    {
        $this->permissionCheck('admission_add');
        return app(InstituteAdmissionController::class)->feePayment($student);
    }

    public function skipFeePayment(Student $student)
    {
        $this->permissionCheck('admission_add');
        return app(InstituteAdmissionController::class)->skipFeePayment($student);
    }

    public function quickSuccess(Student $student)
    {
        $this->permissionCheck('admission_add');
        return app(InstituteAdmissionController::class)->quickSuccess($student);
    }

    public function printAll(Student $student, \App\Models\FeeInvoice $invoice = null)
    {
        $this->permissionCheck('admission_add');
        return app(InstituteAdmissionController::class)->printAll($student, $invoice);
    }
}
