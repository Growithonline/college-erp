<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Institute\Admission\AdmissionController as InstituteAdmissionController;
use App\Http\Controllers\Institute\Master\StudentTypeController;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseStream;
use App\Models\CourseType;
use App\Models\FeePlan;
use App\Models\Institute;
use App\Models\Student;
use App\Models\TransportRoute;
use App\Models\TransportRouteStop;
use App\Models\TransportVehicle;
use App\Models\TransportDriver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CenterStudentController extends Controller
{
    private function center()
    {
        return Auth::guard('center')->user();
    }

    private function permissionCheck(string $permission): void
    {
        abort_unless($this->center()->hasPermission($permission), 403, 'You do not have permission to perform this action.');
    }

    // ── Student List ───────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->permissionCheck('student_view');
        $center = $this->center();

        $instituteId   = $center->institute_id;
        $activeSession = AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->first();

        $allSessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();

        $permsMap = $center->sessionPermsMap();
        $allowedSessions = $permsMap === null
            ? $allSessions
            : $allSessions->filter(fn($s) => (bool) ($permsMap[$s->id]['view'] ?? false))->values();

        $sessionId = $request->filled('session_id')
            ? (int) $request->session_id
            : ($activeSession?->id ?? 0);

        if ($sessionId) {
            abort_unless($center->canViewStudentsInSession($sessionId), 403, 'Access denied for this session.');
        }

        $courses = Course::where('institute_id', $instituteId)->where('status', true)
            ->orderBy('name')->get();

        $query = $this->buildStudentQuery($center, $instituteId, $sessionId, $request);

        $students = $query->paginate(20)->withQueryString();

        return view('center.students.index', compact(
            'students', 'activeSession', 'allowedSessions', 'sessionId', 'courses'
        ));
    }

    public function export(Request $request)
    {
        $this->permissionCheck('student_view');
        $center      = $this->center();
        $instituteId = $center->institute_id;

        $activeSession = AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->first();
        $sessionId     = $request->filled('session_id') ? (int) $request->session_id : ($activeSession?->id ?? 0);

        if ($sessionId) {
            abort_unless($center->canViewStudentsInSession($sessionId), 403, 'Access denied for this session.');
        }

        $students    = $this->buildStudentQuery($center, $instituteId, $sessionId, $request)->get();
        $sessionName = $sessionId ? AcademicSession::find($sessionId)?->name : 'All Sessions';
        $format      = strtolower($request->format ?? 'csv');
        $filename    = 'students-' . now()->format('Ymd-His');

        if ($format === 'pdf') {
            $institute = Institute::find($instituteId);
            $pdf = Pdf::loadView('center.students.export-pdf', compact(
                'students', 'center', 'institute', 'sessionName'
            ))->setPaper('A4', 'landscape');
            return $pdf->download($filename . '.pdf');
        }

        $metaRows = [
            ['My Students — ' . $center->name],
            ['Session: ' . $sessionName, 'Generated: ' . now()->format('d M Y h:i A')],
            [],
        ];
        $headers = [
            '#', 'Student Name', 'Father Name', 'Mother Name', 'Mobile',
            'Student ID', 'Course', 'Stream', 'Year', 'Semester',
            'Session', 'Admitted By', 'Status', 'Admission Date',
        ];
        $rows = $students->values()->map(fn($s, $i) => [
            $i + 1,
            $s->name ?? '',
            $s->father_name ?? '',
            $s->mother_name ?? '',
            $s->mobile ?? '',
            $s->student_uid ?? '',
            $s->stream->course->name ?? '',
            $s->stream->name ?? '',
            $s->coursePart?->year_label ?? '',
            $s->current_semester ? 'S' . $s->current_semester : '',
            $s->session?->name ?? '',
            $s->admittedBy?->name ?? '',
            ucfirst($s->status ?? 'pending'),
            $s->admission_date?->format('d-m-Y') ?? '',
        ])->all();

        $ext = $format === 'excel' ? 'xlsx' : 'csv';
        return response()->streamDownload(function () use ($metaRows, $headers, $rows) {
            $h = fopen('php://output', 'w');
            fprintf($h, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($metaRows as $meta) { fputcsv($h, $meta); }
            fputcsv($h, $headers);
            foreach ($rows as $row) { fputcsv($h, $row); }
            fclose($h);
        }, $filename . '.' . $ext, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.' . $ext . '"',
        ]);
    }

    private function buildStudentQuery($center, int $instituteId, int $sessionId, Request $request)
    {
        $isOwnScope = $sessionId
            ? $center->studentScopeForSession($sessionId) === 'own'
            : $center->isStudentScopeOwn();

        $query = Student::with(['stream.course', 'coursePart', 'session', 'admittedBy'])
            ->where('institute_id', $instituteId);

        if ($isOwnScope) {
            $query->where('admission_source', 'center')->where('admission_source_id', $center->id);
        }

        $query->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId));

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('father_name', 'like', "%{$s}%")
                ->orWhere('mother_name', 'like', "%{$s}%")
                ->orWhere('mobile', 'like', "%{$s}%")
                ->orWhere('student_uid', 'like', "%{$s}%")
            );
        }

        $query->when($request->filled('course_id'), fn($q) =>
            $q->whereHas('stream', fn($sq) => $sq->where('course_id', $request->course_id))
        );

        $query->when($request->filled('status'), fn($q) =>
            $q->where('status', $request->status)
        );

        $query->when($request->filled('from_date'), fn($q) =>
            $q->whereDate('admission_date', '>=', $request->from_date)
        );

        $query->when($request->filled('to_date'), fn($q) =>
            $q->whereDate('admission_date', '<=', $request->to_date)
        );

        return $query->orderByDesc('admission_date');
    }

    // ── Student Profile ────────────────────────────────────────────────────

    public function show(Student $student)
    {
        $this->permissionCheck('student_view');
        $center = $this->center();

        abort_if((int) $student->institute_id !== (int) $center->institute_id, 403, 'Student not found.');

        abort_unless($center->canViewStudentsInSession((int) $student->academic_session_id), 403, 'You do not have permission to view students in this session.');

        // Per-session student scope check
        if ($center->studentScopeForSession((int) $student->academic_session_id) === 'own') {
            abort_if(
                $student->admission_source !== 'center' ||
                (int) $student->admission_source_id !== (int) $center->id,
                403, 'Student not found.'
            );
        }

        $student->load(['stream.course', 'coursePart', 'educationDetails']);

        return view('center.students.show', compact('student', 'center'));
    }

    // ── Global Search ──────────────────────────────────────────────────────

    public function globalSearch(Request $request)
    {
        $this->permissionCheck('student_view');

        $center   = $this->center();
        $sessions = AcademicSession::where('institute_id', $center->institute_id)
            ->orderByDesc('is_active')->orderByDesc('id')->get();

        $filters   = $this->globalSearchFilters($request);
        $sessionId = $request->filled('session_id') ? (int) $request->session_id : null;

        $isInitialLoad = false;
        $students      = null;

        if ($this->hasGlobalSearchFilters($filters)) {
            $students = $this->buildGlobalSearchQuery($center, $filters, $sessionId)
                ->paginate(12)->withQueryString();
        } else {
            $isInitialLoad = true;
            $baseQuery = Student::where('institute_id', $center->institute_id)
                ->with(['stream.course', 'session', 'coursePart'])
                ->orderBy('name');
            $initOwnScope = $sessionId
                ? $center->studentScopeForSession((int) $sessionId) === 'own'
                : $center->isStudentScopeOwn();
            if ($initOwnScope) {
                $baseQuery->where('admission_source', 'center')
                          ->where('admission_source_id', $center->id);
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
            'layout'               => 'center.layout',
            'indexRoute'           => 'center.students.index',
            'searchRoute'          => 'center.students.search',
            'profileRoute'         => 'center.students.show',
            'walletRoute'          => null,
            'historyRoute'         => null,
            'collectFeeRoute'      => 'center.fee.create',
            'showWalletAction'     => false,
            'showHistoryAction'    => false,
            'showCollectFeeAction' => $center->canCollectFee(),
            'listLabel'            => 'My Students',
        ];

        if ($request->ajax() || $request->boolean('_ajax')) {
            return view('institute.students._global-search-results', $viewData);
        }

        return view('institute.students.global-search', $viewData);
    }

    // ── Quick Admission ────────────────────────────────────────────────────

    public function quickCreate()
    {
        $this->permissionCheck('admission_add');
        session()->forget(['quick_admission_preview', 'quickPreviewData']);
        $center = $this->center();
        abort_unless($center->canUseQuickAdmissionForm(), 403, 'Quick admission form not permitted for this center.');

        $instituteId   = $center->institute_id;
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->firstOrFail();

        $allSessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')->orderByDesc('id')->get();
        $admissibleSessions = $allSessions->filter(
            fn($s) => $center->canAdmitInSession($s->id)
        )->values();

        abort_unless($admissibleSessions->isNotEmpty(), 403, 'No admissible sessions found for this center.');

        $formConfig   = \App\Http\Controllers\Institute\Master\AdmissionFormController::getActiveConfig($instituteId, 'quick');
        $studentTypes = StudentTypeController::getActiveTypes($instituteId);

        $courses = Course::where('institute_id', $instituteId)->where('status', true)
            ->with(['streams', 'parts', 'type'])->get();

        // Filter to allowed courses only
        if ($center->allowed_courses !== null) {
            $courses = $courses->whereIn('id', $center->allowed_courses)->values();
        }

        $courseTypeIds = $courses->pluck('course_type_id')->filter()->unique()->values();
        $courseTypes   = CourseType::whereIn('id', $courseTypeIds)->orderBy('sort_order')->orderBy('name')->get();

        $centers = collect([$center]);
        $partners = collect();

        // Payment permissions — single source: PaymentModePermission table
        $allModeKeys = ['cash', 'upi', 'online', 'cheque', 'dd', 'neft', 'rtgs'];
        $perm = \App\Models\PaymentModePermission::where('institute_id', $instituteId)
            ->where('user_type', 'center')
            ->where('user_id', $center->id)
            ->first();

        $allowedPaymentModes = $perm
            ? array_values(array_intersect($allModeKeys, $perm->allowed_modes ?? []))
            : $allModeKeys;

        $bankQuery = \App\Models\InstituteBankAccount::where('institute_id', $instituteId)
            ->where('is_active', true)->orderBy('sort_order');
        if ($perm && !empty($perm->allowed_bank_ids)) {
            $bankQuery->whereIn('id', $perm->allowed_bank_ids);
        }
        $bankAccounts = $bankQuery->get()->filter(function ($account) use ($allowedPaymentModes) {
            $bankModes = array_filter(array_map('trim', explode(',', $account->allowed_payment_modes ?? '')));
            return empty($bankModes) || array_intersect($allowedPaymentModes, $bankModes);
        })->values();

        $feePlans = FeePlan::with('installments')
            ->where('institute_id', $instituteId)->where('is_active', true)->orderBy('name')->get();

        return view('center.admission.quick-create', compact(
            'activeSession', 'admissibleSessions', 'formConfig', 'courses', 'courseTypes', 'studentTypes',
            'centers', 'partners', 'center', 'allowedPaymentModes', 'bankAccounts', 'feePlans'
        ));
    }

    public function quickStore(Request $request)
    {
        $this->permissionCheck('admission_add');
        $center = $this->center();
        abort_unless($center->canUseQuickAdmissionForm(), 403, 'Quick admission form not permitted for this center.');

        // Server-side session check
        $sessionId = $request->filled('session_id')
            ? (int) $request->session_id
            : (int) AcademicSession::where('institute_id', $center->institute_id)->where('is_active', true)->value('id');
        abort_unless(
            $center->canAdmitInSession($sessionId),
            403, 'Admission in this session is not permitted for your center.'
        );

        // Server-side course check (via course_stream_id → course_id)
        if ($center->allowed_courses !== null && $request->filled('course_stream_id')) {
            $courseId = CourseStream::where('id', $request->course_stream_id)->value('course_id');
            abort_unless(
                $courseId && $center->isAllowedCourse((int) $courseId),
                403, 'Admission for this course is not permitted for your center.'
            );
        }

        $request->merge([
            'admission_source'    => 'center',
            'admission_source_id' => $center->id,
        ]);

        return app(InstituteAdmissionController::class)->quickStore($request);
    }

    // ── Full Admission ─────────────────────────────────────────────────────

    public function create()
    {
        $this->permissionCheck('admission_add');
        session()->forget(['admission_preview', 'previewData']);
        $center = $this->center();
        abort_unless($center->canUseFullAdmissionForm(), 403, 'Full admission form not permitted for this center.');

        $instituteId   = $center->institute_id;
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->firstOrFail();

        $allSessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')->orderByDesc('id')->get();
        $admissibleSessions = $allSessions->filter(
            fn($s) => $center->canAdmitInSession($s->id)
        )->values();

        abort_unless($admissibleSessions->isNotEmpty(), 403, 'No admissible sessions found for this center.');

        $formConfig   = \App\Http\Controllers\Institute\Master\AdmissionFormController::getActiveConfig($instituteId, 'admission');
        $sections     = \App\Http\Controllers\Institute\Master\AdmissionFormController::getSections('admission');
        $studentTypes = StudentTypeController::getActiveTypes($instituteId);

        $courses = Course::where('institute_id', $instituteId)->where('status', true)
            ->with(['streams', 'parts', 'type'])->get();

        if ($center->allowed_courses !== null) {
            $courses = $courses->whereIn('id', $center->allowed_courses)->values();
        }

        $courseTypeIds = $courses->pluck('course_type_id')->filter()->unique()->values();
        $courseTypes   = CourseType::whereIn('id', $courseTypeIds)->orderBy('sort_order')->orderBy('name')->get();

        $centers  = collect([$center]);
        $partners = collect();

        $transportRoutes  = TransportRoute::with('stops')->where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $transportVehicles = TransportVehicle::where('institute_id', $instituteId)->where('status', true)->orderBy('vehicle_no')->get();
        $transportDrivers  = TransportDriver::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $transportStops    = TransportRouteStop::with('route:id,name')->whereHas('route', fn($q) => $q->where('institute_id', $instituteId))->where('status', true)->orderBy('sequence')->get();

        return view('center.admission.create', compact(
            'activeSession', 'admissibleSessions', 'formConfig', 'sections', 'courses', 'courseTypes', 'studentTypes',
            'centers', 'partners', 'center',
            'transportRoutes', 'transportVehicles', 'transportDrivers', 'transportStops'
        ));
    }

    public function store(Request $request)
    {
        $this->permissionCheck('admission_add');
        $center = $this->center();
        abort_unless($center->canUseFullAdmissionForm(), 403, 'Full admission form not permitted for this center.');

        $sessionId = $request->filled('session_id')
            ? (int) $request->session_id
            : (int) AcademicSession::where('institute_id', $center->institute_id)->where('is_active', true)->value('id');
        abort_unless(
            $center->canAdmitInSession($sessionId),
            403, 'Admission in this session is not permitted for your center.'
        );

        if ($center->allowed_courses !== null && $request->filled('course_stream_id')) {
            $courseId = CourseStream::where('id', $request->course_stream_id)->value('course_id');
            abort_unless(
                $courseId && $center->isAllowedCourse((int) $courseId),
                403, 'Admission for this course is not permitted for your center.'
            );
        }

        $request->merge([
            'admission_source'    => 'center',
            'admission_source_id' => $center->id,
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

    public function feePreview(Request $request)
    {
        $this->permissionCheck('admission_add');
        return app(InstituteAdmissionController::class)->feePreview($request);
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

    // ── Private helpers ────────────────────────────────────────────────────

    private function buildGlobalSearchQuery($center, array $filters, ?int $sessionId = null)
    {
        $query = Student::where('institute_id', $center->institute_id)
            ->with(['stream.course', 'session', 'coursePart'])
            ->orderBy('name');

        $searchOwnScope = $sessionId
            ? $center->studentScopeForSession((int) $sessionId) === 'own'
            : $center->isStudentScopeOwn();
        if ($searchOwnScope) {
            $query->where('admission_source', 'center')
                  ->where('admission_source_id', $center->id);
        }

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }

        $this->applyGlobalSearchFilters($query, $filters);

        return $query;
    }

    private function globalSearchFilters(Request $request): array
    {
        return [
            'student_name'  => trim((string) $request->input('student_name', '')),
            'father_name'   => trim((string) $request->input('father_name', '')),
            'mother_name'   => trim((string) $request->input('mother_name', '')),
            'mobile'        => trim((string) $request->input('mobile', '')),
            'email'         => trim((string) $request->input('email', '')),
            'student_id'    => trim((string) $request->input('student_id', '')),
            'roll_no'       => trim((string) $request->input('roll_no', '')),
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

    private function applyGlobalSearchFilters($query, array $filters): void
    {
        if ($filters['student_name'] !== '') {
            $query->where('name', 'like', '%' . $filters['student_name'] . '%');
        }
        if ($filters['father_name'] !== '') {
            $query->where('father_name', 'like', '%' . $filters['father_name'] . '%');
        }
        if ($filters['mother_name'] !== '') {
            $query->where('mother_name', 'like', '%' . $filters['mother_name'] . '%');
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
            $t = $filters['roll_no'];
            $query->where(function ($b) use ($t) {
                $b->where('roll_no', 'like', "%{$t}%")
                  ->orWhereHas('academicIdentities', fn($q) => $q->where('roll_no', 'like', "%{$t}%")
                      ->orWhere('roll_no_snapshot', 'like', "%{$t}%"));
            });
        }
        if ($filters['enrollment_no'] !== '') {
            $t = $filters['enrollment_no'];
            $query->where(function ($b) use ($t) {
                $b->where('enrollment_no', 'like', "%{$t}%")
                  ->orWhereHas('academicIdentities', fn($q) => $q->where('enrollment_no_snapshot', 'like', "%{$t}%"));
            });
        }
    }
}
