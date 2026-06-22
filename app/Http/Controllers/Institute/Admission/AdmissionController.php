<?php

namespace App\Http\Controllers\Institute\Admission;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Institute\Master\AdmissionFormController;
use App\Models\AcademicSession;
use App\Models\Center;
use App\Models\CenterWallet;
use App\Models\ChannelPartner;
use App\Models\ChannelWallet;
use App\Models\Course;
use App\Models\CourseType;
use App\Models\FeeType;
use App\Models\Institute;
use App\Http\Controllers\Institute\Master\StudentTypeController;
use App\Models\CoursePart;
use App\Models\CourseStream;
use App\Models\CourseStreamSubject;
use App\Models\FeeInvoice;
use App\Models\InstituteBankAccount;
use App\Models\PaymentModePermission;
use App\Models\StudentAcademicChangeLog;
use App\Models\StreamYearSubjectRule;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\TransportAllocation;
use App\Models\TransportDriver;
use App\Models\TransportRoute;
use App\Models\TransportRouteStop;
use App\Models\TransportVehicle;
use App\Models\Subject;
use App\Services\FeeCalculatorService;
use App\Services\AuditLogService;
use App\Services\StudentAcademicChangeService;
use App\Services\StudentIdService;
use App\Models\FeePlan;
use App\Services\WalletService;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Mail\StudentCredentialsMail;
use App\Services\InstituteMailer;
use App\Services\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdmissionController extends Controller
{
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

    private function currentStaff(): ?\App\Models\StaffMember
    {
        return auth()->guard('staff')->user();
    }

    private function panelPrefix(): string
    {
        if (auth()->guard('staff')->check()) {
            return 'staff';
        }

        if (auth()->guard('center')->check()) {
            return 'center';
        }

        if (auth()->guard('partner')->check()) {
            return 'partner';
        }

        return 'institute';
    }

    private function admissionRoute(string $name): string
    {
        $prefix = $this->panelPrefix();

        return $prefix === 'institute'
            ? "admissions.{$name}"
            : "{$prefix}.admissions.{$name}";
    }

    private function canCollectFee(): bool
    {
        $user = $this->authenticatedUser();

        if (!$user) {
            return false;
        }

        return match ($this->panelPrefix()) {
            'staff'   => $user->hasPermission('fee_collect'),
            'center'  => (bool) ($user->can_collect_fee ?? false) || (bool) ($user->can_add_admission ?? false),
            'partner' => (bool) ($user->can_collect_fee ?? false) || (bool) ($user->can_add_admission ?? false),
            default   => true,
        };
    }

    private function admissibleSessions(int $instituteId): \Illuminate\Support\Collection
    {
        $all = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        $panel = $this->panelPrefix();
        if ($panel === 'institute') {
            return $all;
        }

        $user = $this->authenticatedUser();
        if (!$user) {
            return $all;
        }

        if ($panel === 'staff') {
            if (!($user->restrict_session_access ?? false)) {
                return $all;
            }
            $allowed = array_map('intval', $user->allowed_session_ids ?? []);
            return $all->filter(fn($s) => in_array($s->id, $allowed))->values();
        }

        // Center / ChannelPartner — use allowed_sessions JSON
        $allowedSessions = $user->allowed_sessions ?? null;
        if (empty($allowedSessions)) {
            return $all;
        }

        $admissibleIds = collect($allowedSessions)
            ->filter(fn($p) => !empty($p['admission']))
            ->pluck('id')
            ->map('intval')
            ->all();

        return $all->filter(fn($s) => in_array($s->id, $admissibleIds))->values();
    }

    private function resolveAdmissionSession(int $instituteId, ?int $requestedId): AcademicSession
    {
        $active = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->firstOrFail();

        if (!$requestedId || $requestedId === $active->id) {
            return $active;
        }

        $session = AcademicSession::where('institute_id', $instituteId)->findOrFail($requestedId);

        $panel = $this->panelPrefix();
        if ($panel === 'institute') {
            return $session;
        }

        $user = $this->authenticatedUser();

        if ($panel === 'staff') {
            if (!($user->restrict_session_access ?? false)) {
                return $session;
            }
            $allowed = array_map('intval', $user->allowed_session_ids ?? []);
            abort_unless(in_array($session->id, $allowed), 403, 'You do not have permission to admit to this session.');
            return $session;
        }

        $allowedSessions = $user->allowed_sessions ?? null;
        if (empty($allowedSessions)) {
            return $session;
        }

        $hasPermission = collect($allowedSessions)
            ->first(fn($p) => (int) ($p['id'] ?? 0) === $session->id && !empty($p['admission']));

        abort_unless($hasPermission, 403, 'You do not have permission to admit to this session.');
        return $session;
    }

    private function admissionCreateView(): string
    {
        return match ($this->panelPrefix()) {
            'staff' => 'staff.admissions.create',
            'center' => 'center.admission.create',
            default => 'institute.admission.create',
        };
    }

    private function quickCreateView(): string
    {
        return match ($this->panelPrefix()) {
            'staff' => 'staff.admissions.quick-create',
            'center' => 'center.admission.quick-create',
            'partner' => 'partner.admissions.quick-create',
            default => 'institute.admission.quick-create',
        };
    }

    private function instituteId(): int
    {
        $user = $this->authenticatedUser();

        abort_if(!$user || !$user->institute_id, 403, 'Institute context missing.');

        return (int) $user->institute_id;
    }

    private function transportSelectionData(int $instituteId): array
    {
        return [
            'transportRoutes' => TransportRoute::with('stops')
                ->where('institute_id', $instituteId)
                ->where('status', true)
                ->orderBy('name')
                ->get(),
            'transportVehicles' => TransportVehicle::where('institute_id', $instituteId)
                ->where('status', true)
                ->orderBy('vehicle_no')
                ->get(),
            'transportDrivers' => TransportDriver::where('institute_id', $instituteId)
                ->where('status', true)
                ->orderBy('name')
                ->get(),
            'transportStops' => TransportRouteStop::with('route:id,name')
                ->whereHas('route', fn ($query) => $query->where('institute_id', $instituteId))
                ->where('status', true)
                ->orderBy('sequence')
                ->get(),
        ];
    }

    private function syncTransportAllocationForAdmission(Student $student, array $formData): void
    {
        if (empty($formData['transport_use'])) {
            return;
        }

        $routeId = (int) ($formData['transport_route_id'] ?? 0);
        if ($routeId <= 0) {
            return;
        }

        $route = TransportRoute::where('institute_id', $student->institute_id)
            ->where('status', true)
            ->findOrFail($routeId);

        $stop = null;
        if (!empty($formData['transport_route_stop_id'])) {
            $stop = TransportRouteStop::where('transport_route_id', $route->id)
                ->where('status', true)
                ->findOrFail((int) $formData['transport_route_stop_id']);
        }

        $vehicle = null;
        if (!empty($formData['transport_vehicle_id'])) {
            $vehicle = TransportVehicle::where('institute_id', $student->institute_id)
                ->where('status', true)
                ->findOrFail((int) $formData['transport_vehicle_id']);
        }

        $driver = null;
        if (!empty($formData['transport_driver_id'])) {
            $driver = TransportDriver::where('institute_id', $student->institute_id)
                ->where('status', true)
                ->findOrFail((int) $formData['transport_driver_id']);
        }

        $existing = TransportAllocation::where('student_id', $student->id)
            ->where('academic_session_id', $student->academic_session_id)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            $existing->update([
                'is_active' => false,
                'status' => 'closed',
                'end_date' => now()->toDateString(),
            ]);
        }

        $allocation = TransportAllocation::create([
            'student_id' => $student->id,
            'institute_id' => $student->institute_id,
            'academic_session_id' => $student->academic_session_id,
            'transport_route_id' => $route->id,
            'transport_route_stop_id' => $stop?->id,
            'transport_vehicle_id' => $vehicle?->id,
            'transport_driver_id' => $driver?->id,
            'fee_amount' => (float) ($formData['transport_fee_amount'] ?? $route->fee_amount),
            'charged_amount' => 0,
            'paid_amount' => 0,
            'start_date' => $formData['transport_start_date'] ?? now()->toDateString(),
            'status' => 'active',
            'is_active' => true,
            'remarks' => $formData['transport_remarks'] ?? null,
        ]);

        if ($requestChargeNow = (bool) ($formData['transport_charge_now'] ?? true)) {
            WalletService::chargeTransportAllocation($allocation);
        }
    }

    private function ensureStaffCanAccessCourseSelection(?int $courseId, ?int $streamId): void
    {
        $staff = $this->currentStaff();

        if (!$staff) {
            return;
        }

        if ($streamId) {
            abort_if(!$staff->canAccessCourseStream($streamId), 403, 'Selected course is outside your access scope.');
            return;
        }

        if ($courseId) {
            abort_if(!$staff->canAccessCourse($courseId), 403, 'Selected course is outside your access scope.');
        }
    }

    private function ensureStaffCanAccessStudent(Student $student): void
    {
        $staff = $this->currentStaff();

        if ($staff) {
            abort_if(!$staff->canAccessStudentForAdmissions($student), 403, 'This student is outside your access scope.');
        }
    }

    private function ensureStaffCanReviewStudent(Student $student): void
    {
        $staff = $this->currentStaff();

        if ($staff) {
            abort_if(!$staff->canAccessStudentForOperations($student), 403, 'This student is outside your access scope.');
        }
    }

    private function initialAdmissionStatus(): string
    {
        return 'pending';
    }

    public function resendCredentials(Student $student)
    {
        if ($student->institute_id && $student->institute_id !== $this->instituteId()) abort(403);

        $plainPassword = Str::random(10);
        $student->update([
            'password'       => Hash::make($plainPassword),
            'portal_enabled' => true,
            'first_login'    => true,
        ]);

        $emailSent = false;
        $smsSent   = false;

        if ($student->email) {
            try {
                InstituteMailer::queue($student->institute_id, $student->email, new StudentCredentialsMail($student, $plainPassword));
                $emailSent = true;
            } catch (\Throwable) {}
        }

        $mobile = $student->mobile ?? $student->father_mobile;
        if ($mobile) {
            $msg = "Dear {$student->name}, your student portal credentials: ID: {$student->student_uid}, Password: {$plainPassword}. Login: " . route('student.login');
            try {
                SmsService::sendForInstitute($student->institute_id, $mobile, $msg, 'admission');
                $smsSent = true;
            } catch (\Throwable) {}
        }

        $deliveryNote = match(true) {
            $emailSent && $smsSent => 'Email aur SMS dono send ho gaye.',
            $emailSent             => 'Email send ho gayi. (Mobile number nahi mila)',
            $smsSent               => 'SMS send ho gaya. (Email nahi mili)',
            default                => 'Email/SMS nahi bheja ja saka — student ka koi contact nahi mila.',
        };

        return back()->with('credentials_reset', [
            'password' => $plainPassword,
            'delivery' => $deliveryNote,
            'name'     => $student->name,
            'uid'      => $student->student_uid,
        ]);
    }

    private function sendStudentCredentials(Student $student): void
    {
        $plainPassword = Str::random(10);
        $student->update([
            'password'       => Hash::make($plainPassword),
            'portal_enabled' => true,
            'first_login'    => true,
        ]);

        if ($student->email) {
            try {
                InstituteMailer::queue($student->institute_id, $student->email, new StudentCredentialsMail($student, $plainPassword));
            } catch (\Throwable) {}
        }

        $mobile = $student->mobile ?? $student->father_mobile;
        if ($mobile) {
            $msg = "Dear {$student->name}, your admission is confirmed at "
                . ($student->institute?->name ?? config('app.name'))
                . ". Student ID: {$student->student_uid}, Password: {$plainPassword}. Login: "
                . route('student.login');
            try {
                SmsService::sendForInstitute($student->institute_id, $mobile, $msg, 'admission');
            } catch (\Throwable) {}
        }
    }

    private function docUploadSetting(string $formType): string
    {
        $prefix = $this->panelPrefix();
        if (!in_array($prefix, ['staff', 'center', 'partner'])) {
            return 'skip';
        }
        $user  = $this->authenticatedUser();
        $field = $formType === 'quick' ? 'doc_quick_form_upload' : 'doc_full_form_upload';
        return $user->{$field} ?? 'skip';
    }

    private function canApproveAdmissions(): bool
    {
        if (auth()->guard('staff')->check()) {
            return (bool) auth()->guard('staff')->user()?->canApproveAdmissions();
        }

        return !auth()->guard('center')->check() && !auth()->guard('partner')->check();
    }

    private function ensureAdmissionApprovalAccess(): void
    {
        abort_unless($this->canApproveAdmissions(), 403, 'Admission approval permission required.');
    }

    private function feeApprovalStatus(array $feeSummary): string
    {
        $totalCharged = (float) ($feeSummary['total_charged'] ?? 0);
        $totalCollection = (float) ($feeSummary['total_collection'] ?? 0);
        $totalDiscount = (float) ($feeSummary['total_discount'] ?? 0);
        $totalDue = (float) ($feeSummary['total_due'] ?? 0);

        if ($totalCollection <= 0 && $totalDiscount <= 0) {
            return 'not_paid';
        }

        if ($totalCharged > 0 && $totalDue <= 0) {
            return 'paid';
        }

        return 'partial';
    }

    private function approvalStudentsQuery(int $instituteId)
    {
        $query = Student::with([
            'stream.course',
            'coursePart',
            'session',
            'admittedBy',
            'approvedByStaff',
        ])->where('institute_id', $instituteId);

        if ($staff = $this->currentStaff()) {
            $staff->scopeOperationalStudents($query);
        }

        return $query;
    }

    private function ensureAdmissionExportPermission(Request $request): void
    {
        if (!$request->filled('export')) {
            return;
        }

        $staff = $this->currentStaff();
        abort_unless(!$staff || $staff->canExportReports(), 403, 'Admission export permission required.');
    }

    private function baseAdmissionQuery(int $instituteId)
    {
        $query = Student::with([
            'stream.course.type',
            'session',
            'coursePart',
            'admittedBy',
            'studentSubjects.subject',
        ])->where('institute_id', $instituteId);

        if ($staff = $this->currentStaff()) {
            $staff->scopeAdmissionStudents($query);
        }

        return $query;
    }

    private function applyAdmissionFilters($query, Request $request, ?AcademicSession $activeSession): void
    {
        if ($request->has('session_id')) {
            if ($request->filled('session_id')) {
                $query->where('academic_session_id', (int) $request->session_id);
            }
        } elseif ($activeSession) {
            $query->where('academic_session_id', $activeSession->id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($studentQuery) use ($search) {
                $studentQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('student_uid', 'like', "%{$search}%")
                    ->orWhere('father_name', 'like', "%{$search}%")
                    ->orWhere('roll_no', 'like', "%{$search}%")
                    ->orWhere('enrollment_no', 'like', "%{$search}%");
            });
        }

        if ($request->filled('course_type_id')) {
            $courseTypeId = (int) $request->course_type_id;
            $query->whereHas('stream.course', fn($courseQuery) => $courseQuery->where('course_type_id', $courseTypeId));
        }

        if ($request->filled('course_id')) {
            $courseId = (int) $request->course_id;
            $query->whereHas('stream', fn($streamQuery) => $streamQuery->where('course_id', $courseId));
        }

        if ($request->filled('course_stream_id')) {
            $query->where('course_stream_id', (int) $request->course_stream_id);
        }

        if ($request->filled('subject_id')) {
            $subjectId = (int) $request->subject_id;
            $selectedSessionId = $request->filled('session_id')
                ? (int) $request->session_id
                : ($activeSession?->id ? (int) $activeSession->id : null);

            $query->whereHas('studentSubjects', function ($subjectQuery) use ($subjectId, $selectedSessionId) {
                $subjectQuery->where('subject_id', $subjectId);

                if ($selectedSessionId) {
                    $subjectQuery->where('academic_session_id', $selectedSessionId);
                }
            });
        }

        if ($request->filled('course_part_id')) {
            $query->where('course_part_id', (int) $request->course_part_id);
        }

        if ($request->filled('current_semester')) {
            $query->where('current_semester', (int) $request->current_semester);
        }

        if ($request->filled('admission_date_from')) {
            $query->whereDate('admission_date', '>=', $request->admission_date_from);
        }

        if ($request->filled('admission_date_to')) {
            $query->whereDate('admission_date', '<=', $request->admission_date_to);
        }

        if ($request->filled('admission_source')) {
            $query->where('admission_source', $request->admission_source);

            if ($request->filled('source_detail_id') && in_array($request->admission_source, ['center', 'channel_partner'])) {
                $query->where('admission_source_id', (int) $request->source_detail_id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('form_status')) {
            $mode = $request->string('form_status')->toString();

            if ($mode === 'complete') {
                $query->whereNotNull('name')
                    ->whereNotNull('mobile')
                    ->whereNotNull('father_name')
                    ->whereNotNull('dob')
                    ->whereNotNull('gender')
                    ->whereNotNull('category')
                    ->whereNotNull('course_stream_id')
                    ->whereNotNull('admission_date');
            } elseif ($mode === 'incomplete') {
                $query->where(function ($incompleteQuery) {
                    $incompleteQuery->whereNull('name')
                        ->orWhereNull('mobile')
                        ->orWhereNull('father_name')
                        ->orWhereNull('dob')
                        ->orWhereNull('gender')
                        ->orWhereNull('category')
                        ->orWhereNull('course_stream_id')
                        ->orWhereNull('admission_date');
                });
            }
        }

        $admittedByValues = array_values(array_filter((array) $request->input('admitted_by', []), fn($value) => filled($value)));
        if ($admittedByValues !== []) {
            $query->where(function ($admittedByQuery) use ($admittedByValues) {
                foreach ($admittedByValues as $encoded) {
                    [$type, $id] = array_pad(explode(':', (string) $encoded, 2), 2, null);

                    if ($type === 'staff' && ctype_digit((string) $id)) {
                        $admittedByQuery->orWhere('admitted_by_staff_id', (int) $id);
                        continue;
                    }

                    if ($type === 'center' && ctype_digit((string) $id)) {
                        $admittedByQuery->orWhere(function ($centerQuery) use ($id) {
                            $centerQuery->where('admission_source', 'center')
                                ->where('admission_source_id', (int) $id);
                        });
                        continue;
                    }

                    if ($type === 'partner' && ctype_digit((string) $id)) {
                        $admittedByQuery->orWhere(function ($partnerQuery) use ($id) {
                            $partnerQuery->where('admission_source', 'channel_partner')
                                ->where('admission_source_id', (int) $id);
                        });
                        continue;
                    }

                    if ($type === 'admin') {
                        $admittedByQuery->orWhere(function ($adminQuery) {
                            $adminQuery->whereNull('admitted_by_staff_id')
                                ->where(function ($sourceQuery) {
                                    $sourceQuery->whereNull('admission_source')
                                        ->orWhere('admission_source', 'direct');
                                });
                        });
                    }
                }
            });
        }
    }

    private function admissionFilterOptions(int $instituteId, Request $request): array
    {
        $courseTypes = CourseType::forInstitute($instituteId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $courses = Course::where('institute_id', $instituteId)
            ->where('status', true)
            ->when($request->filled('course_type_id'), fn($query) => $query->where('course_type_id', (int) $request->course_type_id))
            ->with('type')
            ->orderBy('name')
            ->get();

        $streams = CourseStream::whereHas('course', function ($courseQuery) use ($instituteId, $request) {
            $courseQuery->where('institute_id', $instituteId);
            if ($request->filled('course_type_id')) {
                $courseQuery->where('course_type_id', (int) $request->course_type_id);
            }
            if ($request->filled('course_id')) {
                $courseQuery->where('id', (int) $request->course_id);
            }
        })->with('course')->orderBy('name')->get();

        $courseIds = $courses->pluck('id');
        $parts = CoursePart::whereIn('course_id', $courseIds->isNotEmpty() ? $courseIds : [-1])
            ->where('status', true)
            ->with('course')
            ->orderBy('course_id')
            ->orderBy('part_number')
            ->get();

        $subjectQuery = Subject::where('institute_id', $instituteId)->where('status', true)->orderBy('name');
        if ($request->filled('course_stream_id')) {
            $subjectIds = CourseStreamSubject::where('course_stream_id', (int) $request->course_stream_id)
                ->where('is_active', true)
                ->pluck('subject_id');
            $subjectQuery->whereIn('id', $subjectIds->isNotEmpty() ? $subjectIds : [-1]);
        }
        $subjects = $subjectQuery->get();

        $staffMembers = \App\Models\StaffMember::where('institute_id', $instituteId)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $centers = Center::where('institute_id', $instituteId)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $partners = ChannelPartner::where('institute_id', $instituteId)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return compact('courseTypes', 'courses', 'streams', 'parts', 'subjects', 'staffMembers', 'centers', 'partners');
    }

    private function admissionAppliedFilters(Request $request, ?AcademicSession $activeSession, array $options): array
    {
        $filters = [];

        $session = null;
        if ($request->has('session_id')) {
            $session = $request->filled('session_id')
                ? AcademicSession::find((int) $request->session_id)
                : null;
        } else {
            $session = $activeSession;
        }
        if ($session) {
            $filters['Session'] = $session->name;
        }

        if ($request->filled('course_type_id')) {
            $value = $options['courseTypes']->firstWhere('id', (int) $request->course_type_id)?->name;
            if ($value) {
                $filters['Course Type'] = $value;
            }
        }

        if ($request->filled('course_id')) {
            $value = $options['courses']->firstWhere('id', (int) $request->course_id)?->name;
            if ($value) {
                $filters['Course'] = $value;
            }
        }

        if ($request->filled('course_stream_id')) {
            $stream = $options['streams']->firstWhere('id', (int) $request->course_stream_id);
            if ($stream) {
                $filters['Subject / Stream'] = trim(($stream->course->name ?? '') . ' - ' . $stream->name, ' -');
            }
        }

        if ($request->filled('subject_id')) {
            $value = $options['subjects']->firstWhere('id', (int) $request->subject_id)?->name;
            if ($value) {
                $filters['Subject'] = $value;
            }
        }

        if ($request->filled('course_part_id')) {
            $part = $options['parts']->firstWhere('id', (int) $request->course_part_id);
            if ($part) {
                $filters['Course Year / Semester'] = trim(($part->course->name ?? '') . ' - ' . ($part->year_label ?? $part->name ?? 'Year'), ' -');
            }
        }

        if ($request->filled('current_semester')) {
            $filters['Semester'] = 'Semester ' . $request->current_semester;
        }

        if ($request->filled('admission_date_from') || $request->filled('admission_date_to')) {
            $from = $request->filled('admission_date_from') ? date('d M Y', strtotime((string) $request->admission_date_from)) : 'Start';
            $to = $request->filled('admission_date_to') ? date('d M Y', strtotime((string) $request->admission_date_to)) : 'Today';
            $filters['Admission Date'] = $from . ' to ' . $to;
        }

        $admittedByValues = array_values(array_filter((array) $request->input('admitted_by', []), fn($value) => filled($value)));
        if ($admittedByValues !== []) {
            $labels = [];
            foreach ($admittedByValues as $encoded) {
                [$type, $id] = array_pad(explode(':', (string) $encoded, 2), 2, null);
                if ($type === 'staff') {
                    $name = $options['staffMembers']->firstWhere('id', (int) $id)?->name;
                    if ($name) {
                        $labels[] = 'Staff: ' . $name;
                    }
                } elseif ($type === 'center') {
                    $name = $options['centers']->firstWhere('id', (int) $id)?->name;
                    if ($name) {
                        $labels[] = 'Center: ' . $name;
                    }
                } elseif ($type === 'partner') {
                    $name = $options['partners']->firstWhere('id', (int) $id)?->name;
                    if ($name) {
                        $labels[] = 'Partner: ' . $name;
                    }
                } elseif ($type === 'admin') {
                    $labels[] = 'Admin / Direct';
                }
            }

            if ($labels !== []) {
                $filters['Admitted By'] = implode(', ', $labels);
            }
        }

        if ($request->filled('admission_source')) {
            $filters['Admission Source'] = ucwords(str_replace('_', ' ', (string) $request->admission_source));

            if ($request->filled('source_detail_id') && in_array($request->admission_source, ['center', 'channel_partner'])) {
                if ($request->admission_source === 'center') {
                    $centerName = \App\Models\Center::find((int) $request->source_detail_id)?->name;
                    if ($centerName) $filters['Center'] = $centerName;
                } else {
                    $partnerName = \App\Models\ChannelPartner::find((int) $request->source_detail_id)?->name;
                    if ($partnerName) $filters['Channel Partner'] = $partnerName;
                }
            }
        }

        if ($request->filled('status')) {
            $filters['Status'] = ucwords(str_replace('_', ' ', (string) $request->status));
        }

        if ($request->filled('form_status')) {
            $filters['Form Status'] = ucfirst((string) $request->form_status);
        }

        if ($request->filled('search')) {
            $filters['Search'] = trim((string) $request->search);
        }

        return $filters;
    }

    private function admissionExportRows(Collection $students): array
    {
        return $students->map(function (Student $student) {
            $source = $student->admission_source ?? 'direct';
            $admittedBy = match ($source) {
                'center' => 'Center',
                'channel_partner' => 'Partner',
                default => $student->admittedBy?->name ?: 'Admin / Direct',
            };

            if ($source === 'center') {
                $admittedBy = 'Center: ' . (Center::find($student->admission_source_id)?->name ?? 'Unknown');
            } elseif ($source === 'channel_partner') {
                $admittedBy = 'Partner: ' . (ChannelPartner::find($student->admission_source_id)?->name ?? 'Unknown');
            } elseif ($student->admittedBy?->name) {
                $admittedBy = 'Staff: ' . $student->admittedBy->name;
            }

            return [
                $student->student_uid,
                $student->name,
                $student->father_name ?? '-',
                $student->mother_name ?? '-',
                $student->mobile,
                $student->session?->name ?? '-',
                $student->stream?->course?->type?->name ?? '-',
                $student->stream?->course?->name ?? '-',
                $student->stream?->name ?? '-',
                $student->studentSubjects->pluck('subject.name')->filter()->unique()->implode(', '),
                $student->coursePart?->year_label ?? '-',
                $student->current_semester ? 'Semester ' . $student->current_semester : '-',
                $student->admission_date?->format('d M Y') ?? '-',
                $admittedBy,
                ucwords(str_replace('_', ' ', (string) ($student->admission_source ?? 'direct'))),
                ucfirst((string) ($student->status ?? 'active')),
            ];
        })->all();
    }

    private function admissionExportHeaders(): array
    {
        return [
            'Student ID',
            'Student Name',
            'Father Name',
            'Mother Name',
            'Mobile',
            'Session',
            'Course Type',
            'Course',
            'Subject / Stream',
            'Subjects',
            'Course Year',
            'Semester',
            'Admission Date',
            'Admitted By',
            'Admission Source',
            'Status',
        ];
    }

    private function exportAdmissionsCsv(string $filename, array $metaRows, array $headers, array $rows)
    {
        return response()->streamDownload(function () use ($metaRows, $headers, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            foreach ($metaRows as $metaRow) {
                fputcsv($out, $metaRow);
            }

            fputcsv($out, []);
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function exportAdmissionsExcel(string $filename, array $metaRows, array $headers, array $rows)
    {
        if (!class_exists(\ZipArchive::class)) {
            return $this->exportAdmissionsCsv(str_replace('.xlsx', '.csv', $filename), $metaRows, $headers, $rows);
        }

        $sheetRows = [...$metaRows, [], $headers, ...$rows];
        $tempPath = tempnam(sys_get_temp_dir(), 'admission-export-');
        $zip = new \ZipArchive();
        $zip->open($tempPath, \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRels());
        $zip->addFromString('docProps/app.xml', $this->xlsxAppXml());
        $zip->addFromString('docProps/core.xml', $this->xlsxCoreXml());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRels());
        $zip->addFromString('xl/styles.xml', $this->xlsxStylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->admissionXlsxSheetXml($sheetRows));
        $zip->close();

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ])->deleteFileAfterSend(true);
    }

    private function admissionXlsxSheetXml(array $sheetRows): string
    {
        $xmlRows = [];

        foreach ($sheetRows as $rowIndex => $row) {
            $cells = [];
            foreach (array_values($row) as $columnIndex => $value) {
                $cellRef = $this->xlsxColumnName($columnIndex + 1) . ($rowIndex + 1);
                $style = $rowIndex <= 1 || (isset($sheetRows[$rowIndex - 1]) && $sheetRows[$rowIndex - 1] === []) ? ' s="1"' : '';
                $cells[] = '<c r="' . $cellRef . '" t="inlineStr"' . $style . '><is><t>' . $this->xlsxEscape((string) $value) . '</t></is></c>';
            }
            $xmlRows[] = '<row r="' . ($rowIndex + 1) . '">' . implode('', $cells) . '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetData>' . implode('', $xmlRows) . '</sheetData></worksheet>';
    }

    private function xlsxColumnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function xlsxEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function xlsxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function xlsxRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function xlsxAppXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            . 'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>College ERP</Application></Properties>';
    }

    private function xlsxCoreXml(): string
    {
        $timestamp = now()->toAtomString();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
            . 'xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>Admissions Export</dc:title>'
            . '<dc:creator>College ERP</dc:creator>'
            . '<cp:lastModifiedBy>College ERP</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private function xlsxWorkbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Admissions" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function xlsxWorkbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function xlsxStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            . '</styleSheet>';
    }

    private function enforceStaffFeeTypeScope(iterable $items): void
    {
        $staff = $this->currentStaff();

        if (!$staff || !$staff->hasRestrictedFeeCollectionTypes()) {
            return;
        }

        foreach ($items as $item) {
            $feeTypeId = isset($item['fee_type_id']) && $item['fee_type_id'] ? (int) $item['fee_type_id'] : null;
            abort_if(!$staff->canAccessFeeType($feeTypeId), 403, 'One or more fee items are outside your access scope.');
        }
    }

    private function filterFeeDataByStaffScope(array $feeData): array
    {
        $staff = $this->currentStaff();

        if (!$staff || !$staff->hasRestrictedFeeCollectionTypes()) {
            return $feeData;
        }

        $isAllowed = function (array $item) use ($staff): bool {
            // Subject/practical fees have fee_type_id = null — always allow for admission preview
            $type = $item['type'] ?? '';
            if (in_array($type, ['subject', 'practical', 'subject_combined', 'subject_assignment'], true)) {
                return true;
            }

            $feeTypeId = isset($item['fee_type_id']) && $item['fee_type_id'] ? (int) $item['fee_type_id'] : null;

            return $staff->canAccessFeeType($feeTypeId);
        };

        if (!empty($feeData['items']) && is_array($feeData['items'])) {
            $feeData['items'] = array_values(array_filter($feeData['items'], $isAllowed));
        }

        if (!empty($feeData['grouped_items']) && is_array($feeData['grouped_items'])) {
            $feeData['grouped_items'] = array_values(array_filter($feeData['grouped_items'], $isAllowed));
        }

        $feeData['total'] = collect($feeData['items'] ?? [])->sum(fn ($item) => (float) ($item['amount'] ?? 0));

        return $feeData;
    }

    private function actorId(): ?int
    {
        return $this->authenticatedUser()?->id;
    }

    private function feeCreateRouteName(): string
    {
        return match ($this->panelPrefix()) {
            'staff' => 'staff.fee.create',
            'center' => 'center.fee.create',
            'partner' => 'partner.fee.create',
            default => 'fee.create',
        };
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

    private function feeItemKey(array $item): string
    {
        return sha1(implode('|', [
            (string) ($item['type'] ?? $item['item_type'] ?? ''),
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
            'subject', 'subject_combined', 'subject_assignment' => in_array('subject_theory', $allowedCategories, true)
                || $hasName('subject fee')
                || $hasName('subject'),
            'practical', 'practical_combined' => in_array('subject_practical', $allowedCategories, true)
                || $hasName('practical fee')
                || $hasName('practical'),
            default => true,
        };
    }

    private function filterFeeDataByCenterScope(array $feeData): array
    {
        $center = auth()->guard('center')->user();

        if (!$center || !$center->hasRestrictedFeeCollectionTypes()) {
            return $feeData;
        }

        $allowedIds = $center->allowedFeeCollectionTypeIds();
        $permissionMeta = $this->feeTypePermissionMeta($allowedIds);
        $isAllowed = fn (array $item): bool => $this->isRestrictedItemAllowed($item, $allowedIds, $permissionMeta['categories'], $permissionMeta['names']);

        if (!empty($feeData['items']) && is_array($feeData['items'])) {
            $feeData['items'] = array_values(array_filter($feeData['items'], $isAllowed));
        }

        if (!empty($feeData['grouped_items']) && is_array($feeData['grouped_items'])) {
            $feeData['grouped_items'] = array_values(array_filter($feeData['grouped_items'], $isAllowed));
        }

        $feeData['total'] = collect($feeData['items'] ?? [])->sum(fn ($item) => (float) ($item['amount'] ?? 0));

        return $feeData;
    }

    private function buildQuickFeeData(Request $request, AcademicSession $activeSession): array
    {
        $instituteId = $this->instituteId();
        $streamId = (int) ($request->input('stream_id') ?: $request->input('course_stream_id'));
        $subjectIds = $request->input('subject_ids', $request->input('selected_subjects', []));
        $stream = CourseStream::with('course')->findOrFail($streamId);
        $this->ensureStaffCanAccessCourseSelection((int) $stream->course_id, (int) $stream->id);

        $selectedPart = $this->resolveCoursePartForEdit(
            $stream,
            $request->filled('course_part_id') ? (int) $request->input('course_part_id') : null
        );
        $coursePart = $selectedPart?->year_number ?? 1;
        $semester = (int) $request->input('semester', 1);

        $feeData = FeeCalculatorService::calculate(
            instituteId: $instituteId,
            sessionId: $activeSession->id,
            courseId: $stream->course_id,
            coursePart: $coursePart,
            semester: $semester,
            studentType: $request->input('student_type') ?? 'regular',
            admissionSource: $request->input('admission_source') ?? 'direct',
            category: $request->input('category') ?? 'general',
            gender: $request->input('gender') ?? 'other',
            subjectIds: array_map('intval', array_filter((array) $subjectIds, fn ($id) => $id !== null && $id !== '')),
            courseStreamId: $streamId,
            coursePartId: $selectedPart?->id
        );

        $feeData = $this->filterFeeDataByCenterScope($this->filterFeeDataByStaffScope($feeData));

        if (empty($feeData['items'])) {
            $feeData['grouped_items'] = [];
            $feeData['total'] = 0;

            return $feeData;
        }

        $items = collect($feeData['items']);
        $subjectFeeTotal = $items->whereIn('type', ['subject', 'subject_assignment'])->sum('amount');
        $courseFees = $items->whereIn('type', ['course', 'course_assignment'])->values();
        $practicalFees = $items->where('type', 'practical')->values();
        $miscFees = $items->where('type', 'misc')->values();
        $grouped = collect();

        foreach ($courseFees as $item) {
            $grouped->push($item);
        }

        if ($subjectFeeTotal > 0) {
            $grouped->push([
                'type' => 'subject_combined',
                'fee_type_id' => null,
                'label' => 'Subject Fee',
                'subject_id' => null,
                'amount' => $subjectFeeTotal,
                'note' => 'Sem ' . $semester,
            ]);
        }

        foreach ($practicalFees as $item) {
            $grouped->push($item);
        }

        foreach ($miscFees as $item) {
            $grouped->push($item);
        }

        $groupedItems = $grouped->map(function (array $item) {
            $item['item_key'] = $this->feeItemKey($item);

            return $item;
        })->values()->all();
        $feeData['grouped_items'] = $groupedItems;
        $feeData['items'] = $groupedItems;
        $feeData['total'] = collect($feeData['items'] ?? [])->sum(fn ($item) => (float) ($item['amount'] ?? 0));

        return $feeData;
    }

    private function buildQuickCollectableItemMap(Request $request, AcademicSession $activeSession): array
    {
        $feeData = $this->buildQuickFeeData($request, $activeSession);
        $items = collect($feeData['grouped_items'] ?? $feeData['items'] ?? []);

        return $items->mapWithKeys(function (array $item) {
            $amount = (float) ($item['amount'] ?? 0);

            return [$item['item_key'] ?? $this->feeItemKey($item) => [
                'fee_name' => trim((string) ($item['label'] ?? $item['fee_name'] ?? '')),
                'fee_type_id' => isset($item['fee_type_id']) && $item['fee_type_id'] ? (int) $item['fee_type_id'] : null,
                'subject_id' => isset($item['subject_id']) && $item['subject_id'] ? (int) $item['subject_id'] : null,
                'item_type' => (string) ($item['type'] ?? ''),
                'total_fee' => $amount,
                'pending' => $amount,
            ]];
        })->all();
    }

    private function allPaymentModes(): array
    {
        return [
            'cash' => 'Cash',
            'upi' => 'UPI',
            'online' => 'Online',
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
        $user = $this->authenticatedUser();

        if (!$actorType || !$user?->id) {
            return null;
        }

        return PaymentModePermission::where('institute_id', $this->instituteId())
            ->where('user_type', $actorType)
            ->where('user_id', $user->id)
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
        $allowedBankIds ??= $this->allowedBankAccountIds();

        $query = InstituteBankAccount::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->orderBy('sort_order');

        if ($allowedBankIds !== null) {
            // Explicit bank permissions override bank's own mode restrictions
            $query->whereIn('id', $allowedBankIds ?: [-1]);
            return $query->get()->values();
        }

        return $query->get()->filter(function (InstituteBankAccount $account) use ($allowedModes) {
            return !empty(array_intersect(
                $allowedModes,
                $this->parseAllowedModes($account->allowed_payment_modes)
            ));
        })->values();
    }

    private function shouldLockPaymentDate(): bool
    {
        return $this->actorType() !== null;
    }

    private function fieldEnabled(array $formConfig, string $key): bool
    {
        $field = $formConfig[$key] ?? null;

        if (!$field) {
            return false;
        }

        return (bool) ($field['enabled'] ?? false) && (bool) ($field['section_enabled'] ?? true);
    }

    private function fieldRequired(array $formConfig, string $key): bool
    {
        return $this->fieldEnabled($formConfig, $key) && (bool) ($formConfig[$key]['required'] ?? false);
    }

    private function appendFieldRule(array &$rules, array $formConfig, string $key, array|string $baseRules): void
    {
        if (!$this->fieldEnabled($formConfig, $key)) {
            return;
        }

        $baseRules = (array) $baseRules;
        array_unshift($baseRules, $this->fieldRequired($formConfig, $key) ? 'required' : 'nullable');
        $rules[$key] = $baseRules;
    }

    private function strictMobileRules(): array
    {
        return ['regex:/^\d{10}$/'];
    }

    private function strictAadharRules(): array
    {
        return ['regex:/^\d{12}$/'];
    }

    private function strictPincodeRules(): array
    {
        return ['regex:/^\d{6}$/'];
    }

    private function passingYearRules(): array
    {
        return ['digits:4', 'integer', 'between:1900,' . now()->year];
    }

    private function strictNameRules(): array
    {
        return ['regex:/^[A-Za-z][A-Za-z\s\.\'\-]*$/'];
    }

    private function makeAdmissionValidator(Request $request, array $formConfig, bool $withPayment = false)
    {
        $validator = Validator::make($request->all(), $this->admissionValidationRules($formConfig, $withPayment));
        $this->attachAdmissionCrossFieldValidation($validator, $request->all());

        return $validator;
    }

    private function attachAdmissionCrossFieldValidation($validator, array $payload): void
    {
        $validator->after(function ($validator) use ($payload) {
            $dob = data_get($payload, 'dob');
            $admissionDate = data_get($payload, 'admission_date');
            $submittedDate = data_get($payload, 'submitted_date');
            $source = data_get($payload, 'admission_source');
            $sourceId = data_get($payload, 'admission_source_id');

            if ($dob && $admissionDate && strtotime((string) $dob) > strtotime((string) $admissionDate)) {
                $validator->errors()->add('admission_date', 'Admission date cannot be earlier than date of birth.');
            }

            if ($admissionDate && $submittedDate && strtotime((string) $submittedDate) < strtotime((string) $admissionDate)) {
                $validator->errors()->add('submitted_date', 'Submitted date cannot be earlier than admission date.');
            }

            if (in_array($source, ['center', 'channel_partner'], true) && blank($sourceId)) {
                $validator->errors()->add('admission_source_id', 'Please select the admission source.');
            }

            foreach ((array) data_get($payload, 'education', []) as $index => $row) {
                $obtained = data_get($row, 'obtained_marks');
                $max = data_get($row, 'max_marks');
                $percentage = data_get($row, 'percentage');

                if ($obtained !== null && $obtained !== '' && $max !== null && $max !== '' && is_numeric($obtained) && is_numeric($max) && (float) $obtained > (float) $max) {
                    $validator->errors()->add("education.$index.max_marks", 'Max marks must be greater than or equal to obtained marks.');
                }

                if ($percentage !== null && $percentage !== '' && is_numeric($percentage) && (float) $percentage > 100) {
                    $validator->errors()->add("education.$index.percentage", 'Percentage cannot be more than 100.');
                }
            }
        });
    }

    private function studentColumnUsesLegacyEnum(string $column, string $expectedFragment): bool
    {
        static $columnTypes = [];

        // Whitelist: only known internal columns may be inspected
        $allowed = ['special_category', 'nationality', 'gender', 'category', 'admission_type'];
        if (!in_array($column, $allowed, true)) {
            return false;
        }

        if (array_key_exists($column, $columnTypes)) {
            return $columnTypes[$column];
        }

        try {
            // Parameterized — column name is already whitelisted above
            $details = DB::selectOne('SHOW COLUMNS FROM students LIKE ?', [$column]);
            $type = strtolower((string) ($details->Type ?? $details->type ?? ''));
        } catch (\Throwable $e) {
            $type = '';
        }

        return $columnTypes[$column] = str_contains($type, 'enum(') && str_contains($type, strtolower($expectedFragment));
    }

    private function normalizeSpecialCategoryValue(?string $value): string
    {
        $rawValue = trim((string) $value);
        if ($rawValue === '') {
            return 'none';
        }

        if (!$this->studentColumnUsesLegacyEnum('special_category', 'scholarship_quota')) {
            return $rawValue;
        }

        return match (strtolower($rawValue)) {
            'none' => 'none',
            'sports', 'sports_quota' => 'sports_quota',
            'scholarship', 'scholarship_quota' => 'scholarship_quota',
            'others', 'other', 'pwd', 'ex_serviceman', 'ncc' => 'others',
            default => 'others',
        };
    }

    private function normalizeNationalityValue(?string $value): string
    {
        $rawValue = trim((string) $value);
        if ($rawValue === '') {
            return 'indian';
        }

        if (!$this->studentColumnUsesLegacyEnum('nationality', 'sri_lankan')) {
            return $rawValue;
        }

        return match (strtolower($rawValue)) {
            'indian', 'nepali', 'bhutanese', 'sri_lankan', 'others' => strtolower($rawValue),
            default => 'others',
        };
    }

    private function normalizeUppercaseString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return mb_strtoupper($value, 'UTF-8');
    }

    private function normalizeEducationRows(array $educationRows): array
    {
        foreach ($educationRows as &$row) {
            if (!is_array($row)) {
                continue;
            }

            foreach (['exam_name', 'education_stream', 'institute_name', 'roll_number', 'passing_year', 'district', 'division', 'board_university'] as $field) {
                if (array_key_exists($field, $row)) {
                    $row[$field] = $this->normalizeUppercaseString($row[$field]);
                }
            }
        }
        unset($row);

        return $educationRows;
    }

    private function admissionValidationRules(array $formConfig, bool $withPayment = false): array
    {
        $rules = [
            'course_type_id'   => ['required', Rule::exists('course_types', 'id')->where('institute_id', $this->instituteId())],
            'course_id'        => ['nullable', 'exists:courses,id'],
            'course_stream_id' => ['required', 'exists:course_streams,id'],
            'course_part_id' => ['nullable', 'exists:course_parts,id'],
            'fee_plan_id'    => ['nullable', Rule::exists('fee_plans', 'id')->where('institute_id', $this->instituteId())],
            'selected_subjects' => ['nullable', 'array'],
            'selected_subjects.*' => ['nullable','integer'],
            'selected_major_subjects' => ['nullable', 'array'],
            'selected_major_subjects.*' => ['nullable','integer'],
            'selected_minor_subjects' => ['nullable', 'array'],
            'selected_minor_subjects.*' => ['integer'],
            'has_scholarship' => ['nullable', 'boolean'],
            'scholarship_name' => ['nullable', 'string', 'max:100'],
            'scholarship_type' => ['nullable', 'string', 'max:50'],
            'scholarship_authority' => ['nullable', 'string', 'max:100'],
            'scholarship_applied_date' => ['nullable', 'date'],
            'scholarship_amount' => ['nullable', 'numeric', 'min:0'],
            'scholarship_ref_no' => ['nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];

        $educationFields = $withPayment
            ? ['q_edu_10th', 'q_edu_12th', 'q_edu_graduation', 'q_edu_other']
            : ['edu_10th', 'edu_12th', 'edu_graduation', 'edu_other'];

        // Determine applicable edu rows based on selected course type's education_level
        $courseTypeId = request()->input('course_type_id');
        $eduLevel = $courseTypeId
            ? \App\Models\CourseType::where('id', $courseTypeId)
                ->where('institute_id', $this->instituteId())
                ->value('education_level')
            : null;

        $eduRowsByLevel = [
            'ug'          => ['edu_10th','edu_12th','edu_other','q_edu_10th','q_edu_12th','q_edu_other'],
            'pg'          => ['edu_10th','edu_12th','edu_graduation','edu_other','q_edu_10th','q_edu_12th','q_edu_graduation','q_edu_other'],
            'diploma'     => ['edu_10th','edu_12th','edu_other','q_edu_10th','q_edu_12th','q_edu_other'],
            'certificate' => ['edu_10th','edu_other','q_edu_10th','q_edu_other'],
            'phd'         => ['edu_10th','edu_12th','edu_graduation','edu_other','q_edu_10th','q_edu_12th','q_edu_graduation','q_edu_other'],
            'other'       => ['edu_10th','edu_12th','edu_graduation','edu_other','q_edu_10th','q_edu_12th','q_edu_graduation','q_edu_other'],
        ];
        $applicableEduRows = $eduLevel ? ($eduRowsByLevel[$eduLevel] ?? null) : null;

        foreach ($educationFields as $index => $fieldKey) {
            if (!$this->fieldEnabled($formConfig, $fieldKey)) {
                continue;
            }
            if ($applicableEduRows !== null && !in_array($fieldKey, $applicableEduRows, true)) {
                continue;
            }

            $requireEducationRow = $this->fieldRequired($formConfig, $fieldKey);
            $prefix = "education.{$index}";
            $leadRule = $requireEducationRow ? 'required' : 'nullable';

            $rules["{$prefix}.institute_name"] = [$leadRule, 'string', 'max:150'];
            $rules["{$prefix}.education_stream"] = ['nullable', 'string', 'max:50'];
            $rules["{$prefix}.roll_number"] = [$leadRule, 'string', 'max:50'];
            $rules["{$prefix}.passing_year"] = array_merge([$leadRule], $this->passingYearRules());
            $rules["{$prefix}.district"] = [$leadRule, 'string', 'max:100'];
            $rules["{$prefix}.division"] = [$leadRule, 'string', 'max:20'];
            $rules["{$prefix}.board_university"] = [$leadRule, 'string', 'max:150'];
            $rules["{$prefix}.obtained_marks"] = [$leadRule, 'numeric', 'min:0'];
            $rules["{$prefix}.max_marks"] = [$leadRule, 'numeric', 'gt:0'];
            $rules["{$prefix}.percentage"] = ['nullable', 'numeric', 'min:0', 'max:100'];
        }

        $this->appendFieldRule($rules, $formConfig, 'institute_form_no', ['string', 'max:50']);
        $this->appendFieldRule($rules, $formConfig, 'sr_no', ['string', 'max:50']);
        $this->appendFieldRule($rules, $formConfig, 'enrollment_no', ['string', 'max:50']);
        $this->appendFieldRule($rules, $formConfig, 'roll_no', ['string', 'max:50']);
        $this->appendFieldRule($rules, $formConfig, 'exam_form_no', ['string', 'max:50']);
        $this->appendFieldRule($rules, $formConfig, 'uin_no', ['string', 'max:50']);
        $this->appendFieldRule($rules, $formConfig, 'reference_no', ['string', 'max:50']);
        $this->appendFieldRule($rules, $formConfig, 'admission_type', [Rule::in(['new', 'lateral', 'transfer', 're_admission'])]);
        $this->appendFieldRule($rules, $formConfig, 'admission_source', [Rule::in(['direct', 'center', 'channel_partner'])]);
        $this->appendFieldRule($rules, $formConfig, 'gap_year', ['boolean']);
        $this->appendFieldRule($rules, $formConfig, 'admission_date', ['date', 'before_or_equal:today']);
        $this->appendFieldRule($rules, $formConfig, 'submitted_date', ['date', 'before_or_equal:today']);
        $this->appendFieldRule($rules, $formConfig, 'name', array_merge(['string', 'max:100'], $this->strictNameRules()));
        $this->appendFieldRule($rules, $formConfig, 'father_name', array_merge(['string', 'max:100'], $this->strictNameRules()));
        $this->appendFieldRule($rules, $formConfig, 'father_mobile', $this->strictMobileRules());
        $this->appendFieldRule($rules, $formConfig, 'mother_name', array_merge(['string', 'max:100'], $this->strictNameRules()));
        $this->appendFieldRule($rules, $formConfig, 'dob', ['date', 'before_or_equal:today']);
        $this->appendFieldRule($rules, $formConfig, 'gender', [Rule::in(['male', 'female', 'other'])]);
        $this->appendFieldRule($rules, $formConfig, 'mobile', $this->strictMobileRules());
        $this->appendFieldRule($rules, $formConfig, 'email', ['email', 'max:100']);
        $this->appendFieldRule($rules, $formConfig, 'guardian_mobile', $this->strictMobileRules());
        $this->appendFieldRule($rules, $formConfig, 'religion', ['string', 'max:50']);
        $this->appendFieldRule($rules, $formConfig, 'category', ['string', 'max:50']);
        $this->appendFieldRule($rules, $formConfig, 'special_category', ['string', 'max:50']);
        $this->appendFieldRule($rules, $formConfig, 'nationality', ['string', 'max:50']);
        $this->appendFieldRule($rules, $formConfig, 'aadhar_no', $this->strictAadharRules());
        $this->appendFieldRule($rules, $formConfig, 'apaar_no', ['string', 'max:50']);
        $this->appendFieldRule($rules, $formConfig, 'student_type', ['string', 'max:30']);
        $this->appendFieldRule($rules, $formConfig, 'marital_status', [Rule::in(['single', 'married'])]);
        $this->appendFieldRule($rules, $formConfig, 'perm_village', ['string', 'max:100']);
        $this->appendFieldRule($rules, $formConfig, 'perm_post', ['string', 'max:100']);
        $this->appendFieldRule($rules, $formConfig, 'perm_thana', ['string', 'max:100']);
        $this->appendFieldRule($rules, $formConfig, 'perm_district', ['string', 'max:100']);
        $this->appendFieldRule($rules, $formConfig, 'perm_state', ['string', 'max:100']);
        $this->appendFieldRule($rules, $formConfig, 'perm_pincode', $this->strictPincodeRules());
        $this->appendFieldRule($rules, $formConfig, 'comm_address', ['string', 'max:255']);

        if (
            $this->fieldEnabled($formConfig, 'admission_source')
            && in_array(request('admission_source'), ['center', 'channel_partner'], true)
        ) {
            $rules['admission_source_id'] = ['required', 'integer'];
        } else {
            $rules['admission_source_id'] = ['nullable', 'integer'];
        }

        $rules['transport_use'] = ['nullable', 'boolean'];
        $rules['transport_route_id'] = ['nullable', Rule::exists('transport_routes', 'id')->where('institute_id', $this->instituteId())];
        $rules['transport_route_stop_id'] = ['nullable', Rule::exists('transport_route_stops', 'id')];
        $rules['transport_vehicle_id'] = ['nullable', Rule::exists('transport_vehicles', 'id')->where('institute_id', $this->instituteId())];
        $rules['transport_driver_id'] = ['nullable', Rule::exists('transport_drivers', 'id')->where('institute_id', $this->instituteId())];
        $rules['transport_fee_amount'] = ['nullable', 'numeric', 'min:0'];
        $rules['transport_start_date'] = ['nullable', 'date', 'before_or_equal:today'];
        $rules['transport_charge_now'] = ['nullable', 'boolean'];

        if (request()->boolean('transport_use')) {
            $rules['transport_route_id'][] = 'required';
            $rules['transport_start_date'][] = 'required';
        }

        if ($withPayment) {
            $rules['payment_mode'] = ['required', Rule::in(array_keys($this->allPaymentModes()))];
            $rules['payment_date'] = ['required', 'date'];
            $rules['fee_items'] = ['required', 'array', 'min:1'];
            $rules['semester'] = ['required', 'integer', 'min:1', 'max:12'];
            $rules['bank_account_id'] = ['nullable', 'integer', 'exists:institute_bank_accounts,id'];
            $rules['transaction_ref'] = ['nullable', 'string', 'max:100'];
            $rules['remarks'] = ['nullable', 'string', 'max:255'];
            $rules['fee_items.*.fee_name'] = ['nullable', 'string', 'max:150'];
            $rules['fee_items.*.fee_type_id'] = ['nullable', 'integer'];
            $rules['fee_items.*.amount'] = ['nullable', 'numeric', 'min:0'];
            $rules['fee_items.*.fine'] = ['nullable', 'numeric', 'min:0'];
            $rules['fee_items.*.discount'] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    private function firstCoursePartForStream(?int $streamId): ?\App\Models\CoursePart
    {
        if (!$streamId) {
            return null;
        }

        $stream = CourseStream::find($streamId);
        if (!$stream) {
            return null;
        }

        return \App\Models\CoursePart::where('course_id', $stream->course_id)
            ->orderBy('year_number')
            ->orderBy('id')
            ->first();
    }

    private function resolveCoursePartForEdit(CourseStream $stream, ?int $coursePartId, ?Student $student = null): ?CoursePart
    {
        if ($coursePartId) {
            $part = CoursePart::find($coursePartId);
            if ($part && (int) $part->course_id === (int) $stream->course_id) {
                return $part;
            }
        }

        $preferredYear = (int) ($student?->coursePart?->year_number ?? 1);
        $matchingPart = CoursePart::where('course_id', $stream->course_id)
            ->where('year_number', max(1, $preferredYear))
            ->orderBy('part_number')
            ->orderBy('id')
            ->first();

        return $matchingPart ?: $this->firstCoursePartForStream($stream->id);
    }

    private function normalizeSubjectSelection(
        int $streamId,
        int $yearNumber,
        array $majorIds = [],
        array $minorIds = [],
        array $plainIds = [],
        bool $skipMinValidation = false
    ): array {
        $mappings = CourseStreamSubject::where('course_stream_id', $streamId)
            ->where('year_number', $yearNumber)
            ->where('is_active', true)
            ->get();

        $allowed = $mappings->keyBy('subject_id');
        $allowedIds = $allowed->keys()->map(fn($id) => (int) $id)->all();

        $majorIds = array_values(array_unique(array_map('intval', $majorIds)));
        $minorIds = array_values(array_unique(array_map('intval', $minorIds)));
        $plainIds = array_values(array_unique(array_map('intval', $plainIds)));

        $compulsoryRows = $mappings->where('is_chooseable', false)->values();
        $compulsoryIds = $compulsoryRows->pluck('subject_id')->map(fn($id) => (int) $id)->all();

        $invalidIds = array_values(array_diff(
            array_unique(array_merge($majorIds, $minorIds, $plainIds)),
            $allowedIds
        ));

        $majorIds = array_values(array_intersect($majorIds, $allowedIds));
        $minorIds = array_values(array_intersect($minorIds, $allowedIds));
        $plainIds = array_values(array_intersect($plainIds, $allowedIds));

        $majorIds = array_values(array_filter($majorIds, function ($subjectId) use ($allowed) {
            return in_array($allowed[$subjectId]?->subject_role, ['major', 'both'], true);
        }));

        $minorIds = array_values(array_filter($minorIds, function ($subjectId) use ($allowed) {
            return in_array($allowed[$subjectId]?->subject_role, ['minor', 'optional', 'both'], true);
        }));

        $plainChoosableIds = array_values(array_diff($plainIds, $compulsoryIds, $majorIds, $minorIds));

        foreach ($plainChoosableIds as $subjectId) {
            $role = (string) ($allowed[$subjectId]?->subject_role ?? 'minor');
            if (in_array($role, ['major', 'both'], true)) {
                $majorIds[] = $subjectId;
                continue;
            }

            $minorIds[] = $subjectId;
        }

        $majorIds = array_values(array_unique($majorIds));
        $minorIds = array_values(array_diff(array_unique($minorIds), $majorIds));

        $yearRule = StreamYearSubjectRule::where('course_stream_id', $streamId)
            ->where('year_number', $yearNumber)
            ->first();

        $majorMin = (int) ($yearRule?->major_min ?? 0);
        $majorMax = (int) ($yearRule?->major_max ?? 99);
        $minorMin = (int) ($yearRule?->minor_optional_min ?? 0);
        $minorMax = (int) ($yearRule?->minor_optional_max ?? 99);

        if (!$skipMinValidation && count($majorIds) < $majorMin) {
            throw ValidationException::withMessages([
                'selected_major_subjects' => "At least {$majorMin} major subject(s) must be selected.",
            ]);
        }

        if ($majorMax >= 0 && count($majorIds) > $majorMax) {
            throw ValidationException::withMessages([
                'selected_major_subjects' => "You cannot select more than {$majorMax} major subject(s).",
            ]);
        }

        if (!$skipMinValidation && count($minorIds) < $minorMin) {
            throw ValidationException::withMessages([
                'selected_minor_subjects' => "At least {$minorMin} minor subject(s) must be selected.",
            ]);
        }

        if ($minorMax >= 0 && count($minorIds) > $minorMax) {
            throw ValidationException::withMessages([
                'selected_minor_subjects' => "You cannot select more than {$minorMax} minor subject(s).",
            ]);
        }

        if (!empty($invalidIds)) {
            throw ValidationException::withMessages([
                'selected_subjects' => 'Some selected subjects are invalid. Please refresh and try again.',
            ]);
        }

        $subjectRows = [];

        foreach ($compulsoryRows as $row) {
            $subjectRows[] = [
                'subject_id' => (int) $row->subject_id,
                'subject_role' => $row->subject_role ?: 'compulsory',
                'is_auto_included' => true,
            ];
        }

        foreach ($majorIds as $subjectId) {
            $subjectRows[] = [
                'subject_id' => (int) $subjectId,
                'subject_role' => 'major',
                'is_auto_included' => false,
            ];
        }

        foreach ($minorIds as $subjectId) {
            $subjectRows[] = [
                'subject_id' => (int) $subjectId,
                'subject_role' => 'minor',
                'is_auto_included' => false,
            ];
        }

        $finalSubjectIds = array_values(array_unique(array_map(
            fn($row) => (int) $row['subject_id'],
            $subjectRows
        )));

        return [
            'rows' => $subjectRows,
            'subject_ids' => $finalSubjectIds,
            'major_ids' => $majorIds,
            'minor_ids' => $minorIds,
            'compulsory_ids' => $compulsoryIds,
            'year_rule' => [
                'major_min' => $majorMin,
                'major_max' => $majorMax,
                'minor_min' => $minorMin,
                'minor_max' => $minorMax,
            ],
        ];
    }

    // ─── Admission Form ────────────────────────────────────────────────
    public function create()
    {
        $instituteId = $this->instituteId();

        // Clear previous preview session — naya form blank hona chahiye
        session()->forget(['admission_preview', 'previewData']);

        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();

        $formConfig = AdmissionFormController::getActiveConfig($instituteId, 'admission');
        $sections   = AdmissionFormController::getSections();

        $centers  = Center::where('institute_id', $instituteId)->where('status', true)->get();
        $partners = ChannelPartner::where('institute_id', $instituteId)->where('status', true)->get();
        $allowedPaymentModes = $this->allowedPaymentModes();
        $bankAccounts = $this->allowedBankAccounts($instituteId, $allowedPaymentModes);
        $courses      = Course::where('institute_id', $instituteId)->where('status', true)
                            ->when($this->currentStaff()?->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('id', $this->currentStaff()->allowedCourseIds() ?: [-1]))
                            ->with('streams', 'parts', 'type')->get();
        $courseTypes  = CourseType::forInstitute($instituteId)->active()->orderBy('sort_order')->orderBy('name')->get();
        $studentTypes = StudentTypeController::getActiveTypes($instituteId);
        $transportData = $this->transportSelectionData($instituteId);

        $admissibleSessions = $this->admissibleSessions($instituteId);
        $feePlans = FeePlan::with('installments')
            ->where('institute_id', $instituteId)->where('is_active', true)->orderBy('name')->get();

        return view($this->admissionCreateView(), compact(
            'activeSession', 'admissibleSessions', 'formConfig', 'sections',
            'centers', 'partners', 'courses', 'courseTypes', 'studentTypes', 'feePlans'
        ) + $transportData);
    }


    // ─── Edit Preview — Session data se form wapas fill karo ────────────
    public function editPreview()
    {
        $formData = session('admission_preview');
        if (!$formData) {
            return redirect()->route($this->admissionRoute('create'))
                ->with('info', 'Session expired. Please fill the form again.');
        }

        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();
        $formConfig    = AdmissionFormController::getActiveConfig($instituteId, 'admission');
        $sections      = AdmissionFormController::getSections();
        $centers       = Center::where('institute_id', $instituteId)->where('status', true)->get();
        $partners      = ChannelPartner::where('institute_id', $instituteId)->where('status', true)->get();
        $courseTypes   = CourseType::forInstitute($instituteId)->active()->orderBy('sort_order')->orderBy('name')->get();
        $studentTypes  = StudentTypeController::getActiveTypes($instituteId);
        $courses       = Course::where('institute_id', $instituteId)->where('status', true)
                            ->when($this->currentStaff()?->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('id', $this->currentStaff()->allowedCourseIds() ?: [-1]))
                            ->with(['streams', 'parts'])->get();
        $transportData = $this->transportSelectionData($instituteId);
        $feePlans = FeePlan::with('installments')
            ->where('institute_id', $instituteId)->where('is_active', true)->orderBy('name')->get();

        // Session mein store karo — view mein $pd se read hoga
        session()->put('previewData', $formData);

        return view($this->admissionCreateView(), compact(
            'activeSession', 'formConfig', 'sections',
            'centers', 'partners', 'courses', 'courseTypes', 'studentTypes', 'feePlans'
        ) + $transportData)->with('previewEdit', true)
          ->with('pd', $formData); // Direct PHP variable bhi pass karo
    }

    // ─── Step 1→2: Form submit → Session mein save → Preview ──────────
    public function storePreview(Request $request)
    {
        $instituteId = $this->instituteId();
        $formConfig = AdmissionFormController::getActiveConfig($instituteId, 'admission');
        $this->ensureStaffCanAccessCourseSelection(
            $request->filled('course_id') ? (int) $request->course_id : null,
            $request->filled('course_stream_id') ? (int) $request->course_stream_id : null
        );

        // Save photo to temp BEFORE validation so old('photo_temp') survives a failed redirect
        $photoPath = $request->input('photo_temp');
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('students/photos/temp', 'public');
            $request->merge(['photo_temp' => $photoPath]);
        }

        $this->makeAdmissionValidator($request, $formConfig)->validate();

        $firstPart = $this->firstCoursePartForStream((int) $request->course_stream_id);
        if ($firstPart) {
            $request->merge(['course_part_id' => $firstPart->id]);
        }

        $selectedMajorSubjectIds = array_values(array_unique(array_map(
            'intval',
            $request->input('selected_major_subjects', [])
        )));
        $selectedMinorSubjectIds = array_values(array_unique(array_map(
            'intval',
            $request->input('selected_minor_subjects', [])
        )));
        $selectedCompulsoryIds = array_values(array_unique(array_map(
            'intval',
            $request->input('selected_subjects', [])
        )));
        $selectedSubjectIds = array_values(array_unique(array_merge(
            $selectedCompulsoryIds,
            $selectedMajorSubjectIds,
            $selectedMinorSubjectIds
        )));

        $this->normalizeSubjectSelection(
            (int) $request->course_stream_id,
            $firstPart?->year_number ?? 1,
            $selectedMajorSubjectIds,
            $selectedMinorSubjectIds,
            $selectedCompulsoryIds
        );

        $formData = $request->except(['_token', 'photo']);
        $formData['photo_temp'] = $photoPath;
        $formData['selected_subjects'] = $selectedSubjectIds;
        $formData['selected_major_subjects'] = $selectedMajorSubjectIds;
        $formData['selected_minor_subjects'] = $selectedMinorSubjectIds;

        session(['admission_preview' => $formData]);

        $activeSession = $this->resolveAdmissionSession(
            $instituteId,
            $request->filled('session_id') ? (int) $request->session_id : null
        );

        $stream = CourseStream::with('course')->find($request->course_stream_id);
        $part   = $firstPart;

        $selectedSubjectIds = array_filter(array_map('intval', $selectedSubjectIds));
        $selectedSubjects   = \App\Models\Subject::whereIn('id', $selectedSubjectIds)
            ->get(['id', 'name', 'code', 'has_practical']);

        $yearNumber         = $part?->year_number ?? 1;
        $compulsorySubjects = CourseStreamSubject::with('subject')
            ->where('course_stream_id', $request->course_stream_id)
            ->where('year_number', $yearNumber)
            ->where('is_chooseable', false)
            ->where('is_active', true)
            ->get();

        // Fee preview
        $semester = 1;
        $feeData  = \App\Services\FeeCalculatorService::calculate(
            instituteId:     $instituteId,
            sessionId:       $activeSession->id,
            courseId:        $stream->course_id,
            coursePart:      $yearNumber,
            semester:        $semester,
            studentType:     $request->student_type     ?? 'regular',
            admissionSource: $request->admission_source ?? 'direct',
            category:        $request->category         ?? 'general',
            gender:          $request->gender           ?? 'other',
            subjectIds:      array_values($selectedSubjectIds),
            courseStreamId:  $request->course_stream_id ? (int) $request->course_stream_id : null,
            coursePartId:    $request->course_part_id ? (int) $request->course_part_id : null,
        );

        $institute = \App\Models\Institute::find($instituteId);
        $transportData = $this->transportSelectionData($instituteId);

        return view('institute.admission.preview', compact(
            'formData', 'activeSession', 'stream', 'part',
            'selectedSubjects', 'compulsorySubjects', 'feeData', 'institute', 'formConfig'
        ) + $transportData);
    }

    // ─── Step 2→3: Preview confirm → DB save ──────────────────────────
    public function confirmStore(Request $request)
    {
        $instituteId = $this->instituteId();
        $formData    = session('admission_preview');

        if (!$formData) {
            return redirect()->route($this->admissionRoute('create'))
                ->with('error', 'Session expired. Please fill the form again.');
        }
        $this->ensureStaffCanAccessCourseSelection(
            isset($formData['course_id']) ? (int) $formData['course_id'] : null,
            isset($formData['course_stream_id']) ? (int) $formData['course_stream_id'] : null
        );

        $activeSession = $this->resolveAdmissionSession(
            $instituteId,
            isset($formData['session_id']) ? (int) $formData['session_id'] : null
        );

        // ── Seat availability check ──────────────────────────────────
        $streamId = $formData['course_stream_id'] ?? null;
        if ($streamId) {
            $seatCheck = \App\Http\Controllers\Institute\Master\CourseStreamController::checkSeatAvailability(
                (int) $streamId, $activeSession->id
            );
            if (!$seatCheck['available']) {
                return redirect()->route($this->admissionRoute('create'))
                    ->with('error', "❌ Seats Full! This stream had {$seatCheck['limit']} seats which are now all filled. No new admission can be created.");
            }
        }

        $year      = StudentIdService::getYearFromSession($activeSession->name);
        $firstPart = $this->firstCoursePartForStream((int) ($formData['course_stream_id'] ?? 0));
        if ($firstPart) {
            $formData['course_part_id'] = $firstPart->id;
        }

        $selectedSubjectIds = array_filter(
            array_map('intval', $formData['selected_subjects'] ?? [])
        );
        $selectedMajorIds = array_values(array_unique(array_map(
            'intval',
            $formData['selected_major_subjects'] ?? []
        )));
        $selectedMinorIds = array_values(array_unique(array_map(
            'intval',
            $formData['selected_minor_subjects'] ?? []
        )));

        $yearNumber = $firstPart?->year_number ?? 1;
        $subjectSelection = $this->normalizeSubjectSelection(
            (int) ($formData['course_stream_id'] ?? 0),
            (int) $yearNumber,
            $selectedMajorIds,
            $selectedMinorIds,
            array_values($selectedSubjectIds)
        );

        $student = null;
        $studentId = null;

        try {
        DB::transaction(function () use (
            $formData, $instituteId, $activeSession, $year, $firstPart,
            $selectedSubjectIds, $selectedMajorIds, $selectedMinorIds,
            $subjectSelection, $yearNumber, &$student, &$studentId
        ) {
            $studentId = StudentIdService::generateStudentId($instituteId, $year);
            $student = Student::create([
                'institute_id'        => $instituteId,
                'academic_session_id' => $activeSession->id,
                'student_uid'         => $studentId,
                'institute_form_no'   => $formData['institute_form_no']   ?? null,
                'sr_no'               => $formData['sr_no']               ?? null,
                'enrollment_no'       => $formData['enrollment_no']       ?? null,
                'roll_no'             => $formData['roll_no']             ?? null,
                'exam_form_no'        => $formData['exam_form_no']        ?? null,
                'uin_no'              => $formData['uin_no']              ?? null,
                'reference_no'        => $formData['reference_no']        ?? null,
                'admission_type'      => $formData['admission_type']      ?? 'new',
                'admission_source'    => $formData['admission_source']    ?? 'direct',
                'admission_source_id' => $formData['admission_source_id'] ?? null,
                'gap_year'            => (bool) ($formData['gap_year']    ?? false),
                'admission_date'      => $formData['admission_date']      ?? now()->toDateString(),
                'submitted_date'      => $formData['submitted_date']      ?? now()->toDateString(),
                'name'                => $this->normalizeUppercaseString($formData['name']),
                'mobile'              => $formData['mobile'] ?? null,
                'email'               => $formData['email']               ?? null,
                'dob'                 => $formData['dob']                 ?? null,
                'gender'              => $formData['gender']              ?? null,
                'religion'            => $formData['religion']            ?? null,
                'category'            => $formData['category']            ?? null,
                'special_category'    => $this->normalizeSpecialCategoryValue($formData['special_category'] ?? null),
                'nationality'         => $this->normalizeUppercaseString($this->normalizeNationalityValue($formData['nationality'] ?? null)),
                'student_type'        => $formData['student_type']        ?? 'regular',
                'aadhar_no'           => $formData['aadhar_no']           ?? null,
                'apaar_no'            => $formData['apaar_no']            ?? null,
                'marital_status'      => $formData['marital_status']      ?? 'single',
                'father_name'         => $this->normalizeUppercaseString($formData['father_name'] ?? null),
                'father_mobile'       => $formData['father_mobile']       ?? null,
                'father_occupation'   => $this->normalizeUppercaseString($formData['father_occupation'] ?? null),
                'mother_name'         => $this->normalizeUppercaseString($formData['mother_name'] ?? null),
                'mother_mobile'       => $formData['mother_mobile']       ?? null,
                'mother_occupation'   => $this->normalizeUppercaseString($formData['mother_occupation'] ?? null),
                'guardian_name'       => $this->normalizeUppercaseString($formData['guardian_name'] ?? null),
                'guardian_mobile'     => $formData['guardian_mobile']     ?? null,
                'guardian_relation'   => $formData['guardian_relation']   ?? null,
                'perm_village'        => $this->normalizeUppercaseString($formData['perm_village'] ?? null),
                'perm_post'           => $this->normalizeUppercaseString($formData['perm_post'] ?? null),
                'perm_thana'          => $this->normalizeUppercaseString($formData['perm_thana'] ?? null),
                'perm_district'       => $this->normalizeUppercaseString($formData['perm_district'] ?? null),
                'perm_state'          => $this->normalizeUppercaseString($formData['perm_state'] ?? null),
                'perm_pincode'        => $formData['perm_pincode']        ?? null,
                'perm_address'        => $this->normalizeUppercaseString($formData['perm_address'] ?? null),
                'comm_same_as_perm'   => (bool) ($formData['comm_same_as_perm'] ?? true),
                'comm_state'          => $this->normalizeUppercaseString($formData['comm_state'] ?? null),
                'comm_district'       => $this->normalizeUppercaseString($formData['comm_district'] ?? null),
                'comm_post'           => $this->normalizeUppercaseString($formData['comm_post'] ?? null),
                'comm_thana'          => $this->normalizeUppercaseString($formData['comm_thana'] ?? null),
                'comm_pincode'        => $formData['comm_pincode']        ?? null,
                'comm_city'           => $this->normalizeUppercaseString($formData['comm_city'] ?? null),
                'comm_address'        => $this->normalizeUppercaseString($formData['comm_address'] ?? null),
                'course_type_id'      => $formData['course_type_id']      ?? null,
                'course_stream_id'    => $formData['course_stream_id'],
                'course_part_id'      => $firstPart?->id ?? ($formData['course_part_id'] ?? null),
                'fee_plan_id'         => !empty($formData['fee_plan_id']) ? (int) $formData['fee_plan_id'] : null,
                'current_semester'    => 1,
                'status'              => $this->initialAdmissionStatus(),
                'admitted_by_staff_id' => auth()->guard('staff')->check() ? auth()->guard('staff')->id() : null,
                'has_scholarship'     => (bool) ($formData['has_scholarship'] ?? false),
                'scholarship_name'    => ($formData['has_scholarship'] ?? false) ? ($formData['scholarship_name'] ?? null) : null,
                'scholarship_type'    => ($formData['has_scholarship'] ?? false) ? ($formData['scholarship_type'] ?? null) : null,
                'scholarship_authority' => ($formData['has_scholarship'] ?? false) ? ($formData['scholarship_authority'] ?? null) : null,
                'scholarship_applied_date' => ($formData['has_scholarship'] ?? false) ? ($formData['scholarship_applied_date'] ?? null) : null,
                'scholarship_amount'  => ($formData['has_scholarship'] ?? false) ? ($formData['scholarship_amount'] ?? null) : null,
                'scholarship_ref_no'  => ($formData['has_scholarship'] ?? false) ? ($formData['scholarship_ref_no'] ?? null) : null,
                'admitted_by_staff_id' => auth()->guard('staff')->check() ? auth()->guard('staff')->id() : null,
            ]);

            // Photo: temp se permanent
            if (!empty($formData['photo_temp'])) {
                $tempPath  = $formData['photo_temp'];
                $finalPath = str_replace('/temp/', '/', $tempPath);
                \Illuminate\Support\Facades\Storage::disk('public')->move($tempPath, $finalPath);
                $student->update(['photo' => $finalPath]);
            }

            // Subjects save
            StudentAcademicChangeService::syncSubjects(
                $student,
                (int) $activeSession->id,
                (int) $yearNumber,
                $subjectSelection['rows']
            );

            // Education
            foreach ($this->normalizeEducationRows($formData['education'] ?? []) as $edu) {
                if (empty($edu['exam_name'])) continue;
                $student->educationDetails()->create([
                    'exam_name'        => $edu['exam_name'],
                    'institute_name'   => $edu['institute_name']   ?? null,
                    'education_stream' => $edu['education_stream'] ?? null,
                    'board_university' => $edu['board_university'] ?? null,
                    'roll_number'      => $edu['roll_number']      ?? null,
                    'passing_year'     => $edu['passing_year']     ?? null,
                    'district'         => $edu['district']         ?? null,
                    'division'         => $edu['division']         ?? null,
                    'obtained_marks'   => $edu['obtained_marks']   ?? null,
                    'max_marks'        => $edu['max_marks']        ?? null,
                    'percentage'       => $edu['percentage']       ?? null,
                ]);
            }

            WalletService::onAdmission($student);
            $this->syncTransportAllocationForAdmission($student, $formData);

            // Academic identity create karo with full snapshot
            $subjectIds = $student->studentSubjects()
                ->where('academic_session_id', $student->academic_session_id)
                ->pluck('subject_id')->toArray();

            \App\Models\StudentAcademicIdentity::firstOrCreate(
                [
                    'student_id'          => $student->id,
                    'academic_session_id' => $student->academic_session_id,
                ],
                [
                    'institute_id'              => $student->institute_id,
                    'course_id'                 => $student->stream->course_id ?? null,
                    'course_stream_id'          => $student->course_stream_id,
                    'course_part_id'            => $student->course_part_id,
                    'semester_at_time'          => $student->current_semester,
                    'subjects_json'             => $subjectIds,
                    'form_no'                   => last(explode('/', $studentId)),
                    'sr_no_snapshot'            => $student->sr_no,
                    'enrollment_no_snapshot'    => $student->enrollment_no,
                    'roll_no_snapshot'          => $student->roll_no,
                    'admission_source_snapshot' => $student->admission_source,
                    'source'                    => 'admission',
                    'admission_type'            => $student->admission_type ?? 'new',
                ]
            );
        });
        } catch (\Throwable $e) {
            return redirect()->route($this->admissionRoute('create'))
                ->with('error', 'Admission could not be saved: ' . $e->getMessage());
        }

        session()->forget(['admission_preview', 'previewData']);

        // Send login credentials to student via email + SMS
        $this->sendStudentCredentials($student);

        if (!$student) {
            return redirect()->route($this->admissionRoute('create'))
                ->with('error', 'Admission could not be saved. Please try again.');
        }

        $docSetting = $this->docUploadSetting('full');

        $goToFeePayment    = $this->canCollectFee();
        $afterDocNextRoute = $goToFeePayment
            ? $this->admissionRoute('fee-payment')
            : $this->admissionRoute('print-all');

        // Fee collection skipped — ensure no stale flag remains
        if (!$goToFeePayment) {
            session()->forget('from_admission_fee_payment');
        }

        if (in_array($docSetting, ['optional', 'required'])) {
            session([
                'doc_upload_setting' => $docSetting,
                'doc_upload_next'    => route($afterDocNextRoute, $student->id),
            ]);
            return redirect()->route($this->admissionRoute('upload-documents'), $student->id)
                ->with('success', "Admission successful! Student ID: {$studentId}. Ab documents upload karo.");
        }

        return redirect()->route($afterDocNextRoute, $student->id)
            ->with('success', "Admission successful! Student ID: {$studentId}");
    }

    // ─── Step 4: Fee Payment Page ──────────────────────────────────────
    public function feePayment(Student $student)
    {
        if ($student->institute_id !== $this->instituteId()) abort(403);
        $this->ensureStaffCanAccessStudent($student);
        session(['from_admission_fee_payment' => $student->id]);

        return redirect()->route($this->feeCreateRouteName(), [
            'student_id' => $student->id,
            'session_id' => $student->academic_session_id,
        ]);
    }

    // ─── Step 4b: Skip Fee Payment ────────────────────────────────────
    public function skipFeePayment(Student $student)
    {
        if ($student->institute_id !== $this->instituteId()) abort(403);
        $this->ensureStaffCanAccessStudent($student);
        session()->forget('from_admission_fee_payment');
        return redirect()->route($this->admissionRoute('print-all'), $student->id)
            ->with('info', 'Fee collection skipped. You can collect the fee later from the student profile.');
    }

    // ─── Step 5: Combined Print Page ──────────────────────────────────
    public function printAll(Student $student, \App\Models\FeeInvoice $invoice = null)
    {
        if ($student->institute_id !== $this->instituteId()) abort(403);
        $this->ensureStaffCanAccessStudent($student);
        $student->load(['stream.course', 'session', 'coursePart', 'educationDetails', 'studentSubjects.subject']);

        $instituteId = $this->instituteId();
        $institute   = \App\Models\Institute::find($instituteId);
        $formConfig  = AdmissionFormController::getActiveConfig($instituteId, 'admission');

        $feeItems = [];
        $remainingDue = 0;
        if ($invoice) {
            if ($invoice->student_id !== $student->id) abort(403);
            $invoice->load(['items.feeType']);
            // Per-fee actual balance from buildPendingRows (accounts for all prior payments + fine)
            $pendingRows  = \App\Services\WalletService::buildPendingRows($student, (int) $invoice->academic_session_id);
            $pendingByFee = $pendingRows->pluck('pending', 'name')->toArray();
            $feeItems = $invoice->items->map(fn($i) => [
                'name'           => $i->fee_name,
                'amount'         => (float) $i->amount,
                'fine'           => (float) ($i->fine ?? 0),
                'discount'       => (float) ($i->discount ?? 0),
                'total_fee'      => (float) ($i->total_fee > 0 ? $i->total_fee : $i->amount),
                'actual_balance' => (float) ($pendingByFee[$i->fee_name] ?? -1),
            ])->toArray();
            // Use buildPendingRows sum for accurate remaining due (not stale main_b)
            $remainingDue = (float) $pendingRows->sum('pending');
        }

        $subjects = $student->studentSubjects()
            ->where('academic_session_id', $student->academic_session_id)
            ->with('subject')->get()->groupBy('subject_role');

        // Resolve admission source name (Center or Channel Partner)
        $admissionSourceName = null;
        if ($student->admission_source === 'center' && $student->admission_source_id) {
            $admissionSourceName = \App\Models\Center::find($student->admission_source_id)?->name;
        } elseif (in_array($student->admission_source, ['channel_partner', 'partner'], true) && $student->admission_source_id) {
            $admissionSourceName = \App\Models\ChannelPartner::find($student->admission_source_id)?->name;
        }

        // Fee collection center label
        $feeCenterLabel = 'Institute';
        if ($invoice) {
            $invoice->loadMissing(['collectedByCenter', 'collectedByPartner']);
            if ($invoice->collected_by_center_id) {
                $feeCenterLabel = 'Center: ' . ($invoice->collectedByCenter?->name ?? 'Unknown');
            } elseif ($invoice->collected_by_partner_id) {
                $feeCenterLabel = 'Partner: ' . ($invoice->collectedByPartner?->name ?? 'Unknown');
            }
        }

        return view('institute.admission.print-all', compact(
            'student', 'institute', 'formConfig', 'invoice', 'feeItems', 'subjects', 'remainingDue', 'admissionSourceName', 'feeCenterLabel'
        ));
    }

    // ─── AJAX — Get subjects for stream ───────────────────────────────
    public function getStreamSeats(Request $request): JsonResponse
    {
        $streamId  = (int) $request->stream_id;
        $sessionId = AcademicSession::where('institute_id', $this->instituteId())
            ->where('is_active', true)->value('id');

        $result = \App\Http\Controllers\Institute\Master\CourseStreamController::checkSeatAvailability(
            $streamId, $sessionId
        );

        return response()->json($result);
    }

    public function getStreamSubjects(Request $request): JsonResponse
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'stream_id'   => [
                'required', 'integer',
                Rule::exists('course_streams', 'id')
                    ->whereIn('course_id', function ($q) use ($instituteId) {
                        $q->select('id')->from('courses')->where('institute_id', $instituteId);
                    }),
            ],
            'year_number' => 'required|integer|min:1|max:10',
        ]);

        $subjects = CourseStreamSubject::with(['subject:id,name,code,has_practical'])
            ->where('course_stream_id', (int) $validated['stream_id'])
            ->where('year_number',      (int) $validated['year_number'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('subject_role')
            ->get()
            ->map(fn($m) => [
                'id'            => $m->subject_id,
                'name'          => $m->subject->name  ?? '',
                'code'          => $m->subject->code  ?? '',
                'has_practical' => (bool) ($m->subject->has_practical ?? false),
                'role'          => $m->subject_role,
                'is_chooseable' => (bool) $m->is_chooseable,
            ]);

        // Year rules (min/max minor)
        $yearRule = \App\Models\StreamYearSubjectRule::where('course_stream_id', (int) $validated['stream_id'])
            ->where('year_number', (int) $validated['year_number'])
            ->first();

        return response()->json([
            'success'   => true,
            'subjects'  => $subjects,
            'year_rule' => $yearRule ? [
                'minor_min' => $yearRule->minor_optional_min,
                'minor_max' => $yearRule->minor_optional_max,
                'major_min' => $yearRule->major_min ?? 1,
                'major_max' => $yearRule->major_max ?? 99,
            ] : ['minor_min' => 0, 'minor_max' => 99, 'major_min' => 1, 'major_max' => 99],
        ]);
    }

    // ─── AJAX — Fee Preview ────────────────────────────────────────────
    public function feePreview(Request $request): JsonResponse
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'stream_id'       => 'required|integer|exists:course_streams,id',
            'academic_session_id' => 'nullable|integer|exists:academic_sessions,id',
            'course_part_id'  => 'nullable|integer|exists:course_parts,id',
            'semester'        => 'nullable|integer|min:1|max:12',
            'subject_ids'     => 'nullable|array',
            'subject_ids.*'   => 'integer|exists:subjects,id',
            'student_type'    => 'nullable|string',
            'admission_source'=> 'nullable|string',
            'category'        => 'nullable|string',
            'gender'          => 'nullable|string',
        ]);

        $stream = CourseStream::with('course')->findOrFail((int) $validated['stream_id']);
        $this->ensureStaffCanAccessCourseSelection((int) $stream->course_id, (int) $stream->id);

        $activeSession = !empty($validated['academic_session_id'])
            ? AcademicSession::where('institute_id', $instituteId)->find($validated['academic_session_id'])
            : AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->first();

        if (!$activeSession) {
            return response()->json(['success' => false, 'message' => 'No active session']);
        }

        $feeData = $this->buildQuickFeeData($request, $activeSession);

        return response()->json([
            'success' => true,
            'fee_data' => $feeData,
        ]);

        // Subject fees combine karo — ek single "Subject Fee" row
        // Order: course fees → Subject Fee → practical fees (per subject) → misc fees
        if (!empty($feeData['items'])) {
            $items           = collect($feeData['items']);
            $subjectFeeTotal = $items->whereIn('type', ['subject', 'subject_assignment'])->sum('amount');
            $courseFees      = $items->whereIn('type', ['course', 'course_assignment'])->values();
            $practicalFees   = $items->where('type', 'practical')->values();
            $miscFees        = $items->where('type', 'misc')->values();

            $grouped = collect();

            // 1. Course fees (Registration Fee, Course Fee, etc.)
            foreach ($courseFees as $item) {
                $grouped->push($item);
            }

            // 2. Subject Fee — combined single row
            if ($subjectFeeTotal > 0) {
                $grouped->push([
                    'type'        => 'subject_combined',
                    'fee_type_id' => null,
                    'label'       => 'Subject Fee',
                    'subject_id'  => null,
                    'amount'      => $subjectFeeTotal,
                    'note'        => 'Sem ' . $semester,
                ]);
            }

            // 3. Practical fees — per subject (alag alag)
            foreach ($practicalFees as $item) {
                $grouped->push($item);
            }

            // 4. Misc fees (Exam Fee, Computer Fee, etc.)
            foreach ($miscFees as $item) {
                $grouped->push($item);
            }

            $feeData['items'] = $grouped->values()->toArray();
        }

    }

    // ─── List ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $instituteId   = $this->instituteId();
        $this->ensureAdmissionExportPermission($request);
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();

        $sessions = AcademicSession::where('institute_id', $instituteId)->orderBy('name')->get();
        $options = $this->admissionFilterOptions($instituteId, $request);
        extract($options);
        $query = $this->baseAdmissionQuery($instituteId);
        $this->applyAdmissionFilters($query, $request, $activeSession);
        $appliedFilters = $this->admissionAppliedFilters($request, $activeSession, $options);

        if ($request->filled('export')) {
            $students = (clone $query)->orderBy('admission_date')->orderBy('id')->get();
            $headers = $this->admissionExportHeaders();
            $rows = $this->admissionExportRows($students);
            $generatedAt = now()->format('d M Y, h:i A');
            $headline = $appliedFilters !== []
                ? 'Filtered By: ' . implode(' | ', array_map(fn($label, $value) => "{$label}: {$value}", array_keys($appliedFilters), $appliedFilters))
                : 'Filtered By: Default listing filters';

            $metaRows = [
                ['Admissions Export Report'],
                [$headline],
                ['Generated At: ' . $generatedAt],
                ['Total Records: ' . number_format($students->count())],
            ];

            $filename = 'admissions-export-' . now()->format('Ymd-His');
            $export = strtolower((string) $request->export);

            if ($export === 'csv') {
                return $this->exportAdmissionsCsv($filename . '.csv', $metaRows, $headers, $rows);
            }

            if ($export === 'excel') {
                return $this->exportAdmissionsExcel($filename . '.xlsx', $metaRows, $headers, $rows);
            }

            if ($export === 'pdf') {
                $institute = Institute::findOrFail($instituteId);
                return Pdf::loadView('institute.admission.export-pdf', [
                    'institute' => $institute,
                    'students' => $students,
                    'headers' => $headers,
                    'rows' => $rows,
                    'appliedFilters' => $appliedFilters,
                    'generatedAt' => $generatedAt,
                ])->setPaper('a4', 'landscape')->download($filename . '.pdf');
            }
        }

        $perPage  = $request->integer('per_page', 20);
        $perPage  = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;
        $students = $query->orderBy('admission_date')->orderBy('id')->paginate($perPage)->withQueryString();

        return view('institute.admission.index', compact(
            'students', 'activeSession', 'sessions', 'perPage', 'appliedFilters',
            'courseTypes', 'courses', 'streams', 'parts', 'subjects', 'staffMembers', 'centers', 'partners'
        ));
    }

    // ─── Online Admissions ─────────────────────────────────────────────
    public function onlineAdmissions(Request $request)
    {
        $instituteId   = (int) auth()->user()->institute_id;
        $activeSession = AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->first();
        $sessions      = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $sessionId     = $request->filled('session_id') ? (int) $request->session_id : $activeSession?->id;

        $courses     = Course::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $courseTypes = CourseType::where('institute_id', $instituteId)->orderBy('name')->get();

        $query = Student::with(['stream.course', 'coursePart'])
            ->where('institute_id', $instituteId)
            ->where('admission_source', 'online')
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId));

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($b) => $b->where('name', 'like', "%{$s}%")
                ->orWhere('mobile', 'like', "%{$s}%")
                ->orWhere('student_uid', 'like', "%{$s}%")
                ->orWhere('father_name', 'like', "%{$s}%"));
        }

        if ($request->filled('course_id')) {
            $query->whereHas('stream', fn($q) => $q->where('course_id', (int) $request->course_id));
        }

        if ($request->filled('course_type_id')) {
            $query->where('course_type_id', (int) $request->course_type_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('admission_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('admission_date', '<=', $request->date_to);
        }

        $perPage  = in_array((int) $request->per_page, [10, 20, 50, 100], true) ? (int) $request->per_page : 20;
        $students = $query->orderByDesc('admission_date')->paginate($perPage)->withQueryString();

        return view('institute.admission.online', compact(
            'students', 'sessions', 'sessionId', 'activeSession',
            'courses', 'courseTypes', 'perPage'
        ));
    }

    // ─── Show ──────────────────────────────────────────────────────────
    public function approvals(Request $request)
    {
        $this->ensureAdmissionApprovalAccess();

        $instituteId = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->first();

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        $courses = Course::where('institute_id', $instituteId)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $courseTypes = CourseType::where('institute_id', $instituteId)
            ->orderBy('name')
            ->get();

        $centers = Center::where('institute_id', $instituteId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $channelPartners = ChannelPartner::where('institute_id', $instituteId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $staffMembers = \App\Models\StaffMember::where('institute_id', $instituteId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $query = $this->approvalStudentsQuery($instituteId);

        if ($request->filled('session_id')) {
            $query->where('academic_session_id', (int) $request->session_id);
        } elseif ($activeSession) {
            $query->where('academic_session_id', $activeSession->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($studentQuery) use ($search) {
                $studentQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('student_uid', 'like', "%{$search}%")
                    ->orWhere('father_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('admission_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('admission_date', '<=', $request->date_to);
        }

        if ($request->filled('course_id')) {
            $query->whereHas('stream', fn($q) => $q->where('course_id', (int) $request->course_id));
        }

        if ($request->filled('course_type_id')) {
            $query->where('course_type_id', (int) $request->course_type_id);
        }

        if ($request->filled('source')) {
            $srcFilter = $request->source;
            if ($srcFilter === 'direct') {
                $query->where(function ($q) {
                    $q->where('admission_source', 'direct')->orWhereNull('admission_source');
                });
                if ($request->filled('source_sub')) {
                    if ($request->source_sub === 'admin') {
                        $query->whereNull('admitted_by_staff_id');
                    } else {
                        $query->where('admitted_by_staff_id', (int) $request->source_sub);
                    }
                }
            } else {
                $query->where('admission_source', $srcFilter);
                if (in_array($srcFilter, ['center', 'channel_partner']) && $request->filled('source_sub')) {
                    $query->where('admission_source_id', (int) $request->source_sub);
                }
            }
        }

        $sortedQuery = fn($q) => $q->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('admission_date');

        if (in_array($request->export, ['csv', 'pdf'], true)) {
            $this->ensureAdmissionExportPermission($request);
            $exportStudents = $sortedQuery($query)->get();

            if ($request->export === 'csv') {
                return response()->streamDownload(function () use ($exportStudents) {
                    $out = fopen('php://output', 'w');
                    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
                    fputcsv($out, ['#', 'Student ID', 'Name', 'Father Name', 'Mother Name', 'Mobile', 'Course', 'Stream', 'Session', 'Admission Date', 'Status', 'Admitted By', 'Approved By', 'Approved At']);
                    foreach ($exportStudents as $i => $s) {
                        $admittedBy = $s->admittedBy?->name ? 'Staff: '.$s->admittedBy->name : 'Admin/Direct';
                        if ($s->admission_source === 'center') $admittedBy = 'Center';
                        elseif ($s->admission_source === 'channel_partner') $admittedBy = 'Channel Partner';
                        fputcsv($out, [
                            $i + 1,
                            $s->student_uid ?? '',
                            $s->name ?? '',
                            $s->father_name ?? '',
                            $s->mother_name ?? '',
                            $s->mobile ?? '',
                            $s->stream?->course?->name ?? '',
                            $s->stream?->name ?? '',
                            $s->session?->name ?? '',
                            $s->admission_date?->format('d M Y') ?? '',
                            ucwords(str_replace('_', ' ', $s->status ?? 'pending')),
                            $admittedBy,
                            $s->approved_by_name ?? '',
                            $s->approved_at?->format('d M Y, h:i A') ?? '',
                        ]);
                    }
                    fclose($out);
                }, 'admission-approvals-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
            }

            if ($request->export === 'pdf') {
                $printTitle = 'Admission Approval Queue';
                $filterLabel = $request->filled('session_id')
                    ? ($sessions->firstWhere('id', $request->session_id)?->name ?? '')
                    : ($activeSession?->name ?? '');
                $institute = \App\Models\Institute::find($instituteId);
                return view('institute.admission.approvals.export-pdf', compact(
                    'exportStudents', 'printTitle', 'filterLabel', 'institute'
                ));
            }
        }

        $perPage = in_array((int) $request->per_page, [10, 20, 50, 100], true) ? (int) $request->per_page : 20;
        $students = $sortedQuery($query)
            ->paginate($perPage)
            ->withQueryString();

        $statsBase = $this->approvalStudentsQuery($instituteId)
            ->when($request->filled('session_id'), fn($statQuery) => $statQuery->where('academic_session_id', (int) $request->session_id), function ($statQuery) use ($activeSession) {
                if ($activeSession) {
                    $statQuery->where('academic_session_id', $activeSession->id);
                }
            });

        $totalAdmissions = (clone $statsBase)->count();
        $pendingAdmissions = (clone $statsBase)->where('status', 'pending')->count();
        $approvedAdmissions = (clone $statsBase)->where('status', 'active')->count();

        return view('institute.admission.approvals.index', compact(
            'students',
            'activeSession',
            'sessions',
            'courses',
            'courseTypes',
            'centers',
            'channelPartners',
            'staffMembers',
            'perPage',
            'totalAdmissions',
            'pendingAdmissions',
            'approvedAdmissions'
        ));
    }

    public function approvalShow(Student $student)
    {
        $this->ensureAdmissionApprovalAccess();
        abort_if($student->institute_id !== $this->instituteId(), 403);
        $this->ensureStaffCanReviewStudent($student);

        $student->load([
            'stream.course.type',
            'session',
            'coursePart',
            'educationDetails',
            'studentSubjects.subject',
            'admittedBy',
            'approvedByStaff',
            'currentAcademicIdentity',
            'admissionDocuments.documentType',
        ]);

        $feeSummary = WalletService::getStudentSummary($student, (int) $student->academic_session_id);
        $feeStatus = $this->feeApprovalStatus($feeSummary);
        $recentInvoices = FeeInvoice::where('student_id', $student->id)
            ->where('is_cancelled', false)
            ->with('items')
            ->latest('id')
            ->limit(5)
            ->get();

        $admissionSourceName = null;
        if ($student->admission_source === 'center' && $student->admission_source_id) {
            $admissionSourceName = Center::find($student->admission_source_id)?->name;
        } elseif ($student->admission_source === 'channel_partner' && $student->admission_source_id) {
            $admissionSourceName = ChannelPartner::find($student->admission_source_id)?->name;
        }

        return view('institute.admission.approvals.show', compact(
            'student',
            'feeSummary',
            'feeStatus',
            'recentInvoices',
            'admissionSourceName'
        ));
    }

    public function approveAdmission(Request $request, Student $student)
    {
        $this->ensureAdmissionApprovalAccess();
        abort_if($student->institute_id !== $this->instituteId(), 403);
        $this->ensureStaffCanReviewStudent($student);

        $validated = $request->validate([
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        $actor = $this->authenticatedUser();
        $student->update([
            'status' => 'active',
            'approved_by_staff_id' => auth()->guard('staff')->check() ? auth()->guard('staff')->id() : null,
            'approved_by_name' => $actor?->name ?? $actor?->email ?? 'Institute Admin',
            'approved_at' => now(),
            'approval_notes' => $validated['approval_notes'] ?? null,
        ]);

        AuditLogService::log($this->instituteId(), 'admission', 'admission_approved', 'Admission approved after verification.', $student, [
            'student_id' => $student->id,
            'student_uid' => $student->student_uid,
            'approved_by' => $actor?->name ?? $actor?->email ?? 'Institute Admin',
        ]);

        return redirect()
            ->route($this->admissionRoute('approvals.show'), $student->id)
            ->with('success', $student->name . '\'s admission has been approved and is now active.');
    }

    public function updateApprovalStatus(Request $request, Student $student)
    {
        $this->ensureAdmissionApprovalAccess();
        abort_if($student->institute_id !== $this->instituteId(), 403);
        $this->ensureStaffCanReviewStudent($student);

        $validated = $request->validate([
            'action' => ['required', \Illuminate\Validation\Rule::in(['approve', 'reject', 'resubmit', 'cancel', 'inactive'])],
            'reason' => ['nullable', 'string', 'max:1000',
                \Illuminate\Validation\Rule::requiredIf(fn() => in_array($request->action, ['reject', 'cancel']))
            ],
        ]);

        $statusMap = [
            'approve'  => 'active',
            'reject'   => 'cancelled',
            'resubmit' => 'pending',
            'cancel'   => 'cancelled',
            'inactive' => 'inactive',
        ];

        $newStatus = $statusMap[$validated['action']];
        $actor = $this->authenticatedUser();

        $updates = [
            'status'        => $newStatus,
            'status_reason' => $validated['reason'] ?? null,
        ];

        if ($validated['action'] === 'approve') {
            $updates['approved_by_staff_id'] = auth()->guard('staff')->check() ? auth()->guard('staff')->id() : null;
            $updates['approved_by_name']     = $actor?->name ?? $actor?->email ?? 'Institute Admin';
            $updates['approved_at']          = now();
        }

        $student->update($updates);

        $actionLabels = [
            'approve'  => 'approved',
            'reject'   => 'rejected',
            'resubmit' => 'sent back for re-submission',
            'cancel'   => 'cancelled',
            'inactive' => 'marked inactive',
        ];

        AuditLogService::log($this->instituteId(), 'admission', 'admission_status_changed',
            'Admission status changed to ' . $newStatus . '.', $student, [
                'student_id'  => $student->id,
                'student_uid' => $student->student_uid,
                'new_status'  => $newStatus,
                'reason'      => $validated['reason'] ?? null,
                'changed_by'  => $actor?->name ?? $actor?->email ?? 'Institute Admin',
            ]
        );

        return redirect()
            ->route($this->admissionRoute('approvals.show'), $student->id)
            ->with('success', $student->name . ' ka admission ' . $actionLabels[$validated['action']] . ' ho gaya.');
    }

    public function show(Student $student, Request $request)
    {
        if ($student->institute_id && $student->institute_id !== $this->instituteId()) abort(403);
        $this->ensureStaffCanAccessStudent($student);

        $student->load([
            'stream.course',
            'session',
            'coursePart',
            'educationDetails',
            'studentSubjects.subject',
            'currentAcademicIdentity',
            'activeTransportAllocation.route',
            'activeTransportAllocation.stop',
            'activeTransportAllocation.vehicle',
            'activeTransportAllocation.driver',
        ]);

        // Har session / semester ki identity load karo
        $sessionIdentities = \App\Models\StudentAcademicIdentity::where('student_id', $student->id)
            ->realOnly()
            ->with(['session', 'courseStream.course', 'coursePart'])
            ->orderBy('academic_session_id')
            ->orderBy('semester_at_time')
            ->orderBy('id')
            ->get()
            ->values();

        $selectedIdentityId = $request->filled('identity_id') ? (int) $request->identity_id : null;
        $requestedSessionId = $request->filled('session_id') ? (int) $request->session_id : (int) $student->academic_session_id;

        $selectedIdentity = $selectedIdentityId
            ? $sessionIdentities->firstWhere('id', $selectedIdentityId)
            : $sessionIdentities
                ->where('academic_session_id', $requestedSessionId)
                ->sortByDesc(fn ($identity) => sprintf(
                    '%d-%03d-%09d',
                    (int) ($identity->semester_at_time === $student->current_semester),
                    (int) ($identity->semester_at_time ?? 0),
                    (int) $identity->id
                ))
                ->first();

        $selectedSessionId = (int) ($selectedIdentity?->academic_session_id ?? $requestedSessionId);

        $selectedContext = WalletService::resolveAcademicContext($student, $selectedSessionId);

        if (!empty($selectedContext['course_part'])) {
            if ($selectedIdentity) {
                $selectedIdentity->setRelation('coursePart', $selectedContext['course_part']);
            } elseif ((int) $student->academic_session_id === $selectedSessionId) {
                $student->setRelation('coursePart', $selectedContext['course_part']);
            }
        }

        // Fee summary for selected session
        $feeSummary = WalletService::getStudentSummary($student, $selectedSessionId);
        $academicChangeLogs = StudentAcademicChangeLog::where('student_id', $student->id)
            ->where('academic_session_id', $selectedSessionId)
            ->latest('id')
            ->get();

        $selectedSubjects = collect();
        if (!empty($selectedIdentity?->subjects_json)) {
            $selectedSubjects = \App\Models\Subject::whereIn('id', $selectedIdentity->subjects_json)
                ->orderBy('name')
                ->get()
                ->map(fn ($subject) => (object) [
                    'name' => $subject->name,
                    'subject_role' => 'recorded',
                ]);
        } else {
            $selectedSubjects = $student->studentSubjects
                ->where('academic_session_id', $selectedSessionId)
                ->map(fn ($studentSubject) => (object) [
                    'name' => $studentSubject->subject?->name ?? '—',
                    'subject_role' => $studentSubject->subject_role ?? 'recorded',
                ])
                ->values();
        }

        return view('institute.admission.profile', compact(
            'student', 'sessionIdentities', 'selectedSessionId', 'selectedIdentity', 'selectedContext', 'feeSummary', 'academicChangeLogs', 'selectedSubjects'
        ));
    }

    // ─── Edit Student Profile ─────────────────────────────────────────
    public function edit(Student $student)
    {
        if ($student->institute_id && $student->institute_id !== $this->instituteId()) abort(403);
        $this->ensureStaffCanAccessStudent($student);
        $instituteId = $this->instituteId();

        $student->load(['stream.course', 'session', 'coursePart', 'educationDetails', 'studentSubjects.subject', 'currentAcademicIdentity']);

        $formConfig  = AdmissionFormController::getActiveConfig($instituteId, 'admission');
        $courses     = Course::where('institute_id', $instituteId)->where('status', true)
                        ->when($this->currentStaff()?->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('id', $this->currentStaff()->allowedCourseIds() ?: [-1]))
                        ->with(['streams', 'parts', 'type'])->get();
        $courseTypes  = CourseType::forInstitute($instituteId)->active()->orderBy('sort_order')->orderBy('name')->get();
        $studentTypes = StudentTypeController::getActiveTypes($instituteId);
        $centers      = Center::where('institute_id', $instituteId)->where('status', true)->get();
        $partners     = ChannelPartner::where('institute_id', $instituteId)->where('status', true)->get();
        $activeSession = \App\Models\AcademicSession::find($student->academic_session_id);
        $feeSummary = WalletService::getStudentSummary($student, (int) $student->academic_session_id);
        $currentSnapshot = StudentAcademicChangeService::buildSnapshot($student);
        $feePlans = FeePlan::with('installments')
            ->where('institute_id', $instituteId)
            ->where(function ($q) use ($student) {
                $q->whereNull('course_id')->orWhere('course_id', $student->stream?->course_id);
            })
            ->orderBy('name')->get();

        return view('institute.admission.edit', compact(
            'student', 'formConfig', 'courses', 'courseTypes', 'studentTypes', 'centers', 'partners', 'activeSession', 'feeSummary', 'currentSnapshot', 'feePlans'
        ));
    }

    // ─── Update Student Profile ────────────────────────────────────────
    public function update(\Illuminate\Http\Request $request, Student $student)
    {
        if ($student->institute_id && $student->institute_id !== $this->instituteId()) abort(403);
        $this->ensureStaffCanAccessStudent($student);
        $this->ensureStaffCanAccessCourseSelection(
            $request->filled('course_id') ? (int) $request->course_id : null,
            $request->filled('course_stream_id') ? (int) $request->course_stream_id : null
        );

        $validator = Validator::make($request->all(), [
            'name'   => ['required', 'string', 'max:100', ...$this->strictNameRules()],
            'mobile' => ['required', ...$this->strictMobileRules()],
            'email' => 'nullable|email|max:100',
            'dob' => 'nullable|date|before_or_equal:today',
            'gender' => 'nullable|in:male,female,other',
            'religion' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:50',
            'special_category' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:50',
            'student_type' => 'nullable|string|max:30',
            'marital_status' => 'nullable|in:single,married',
            'aadhar_no' => ['nullable', ...$this->strictAadharRules()],
            'apaar_no' => 'nullable|string|max:50',
            'father_name' => ['nullable', 'string', 'max:100', ...$this->strictNameRules()],
            'father_mobile' => ['nullable', ...$this->strictMobileRules()],
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => ['nullable', 'string', 'max:100', ...$this->strictNameRules()],
            'mother_mobile' => ['nullable', ...$this->strictMobileRules()],
            'mother_occupation' => 'nullable|string|max:100',
            'guardian_name' => ['nullable', 'string', 'max:100', ...$this->strictNameRules()],
            'guardian_mobile' => ['nullable', ...$this->strictMobileRules()],
            'guardian_relation' => 'nullable|string|max:50',
            'admission_type' => ['nullable', Rule::in(['new', 'lateral', 'transfer', 're_admission'])],
            'admission_source' => ['nullable', Rule::in(['direct', 'center', 'channel_partner'])],
            'admission_source_id' => 'nullable|integer',
            'admission_date' => 'nullable|date|before_or_equal:today',
            'submitted_date' => 'nullable|date|before_or_equal:today',
            'sr_no' => 'nullable|string|max:50',
            'enrollment_no' => 'nullable|string|max:50',
            'roll_no' => 'nullable|string|max:50',
            'exam_form_no' => 'nullable|string|max:50',
            'institute_form_no' => 'nullable|string|max:50',
            'uin_no' => 'nullable|string|max:50',
            'reference_no' => 'nullable|string|max:50',
            'gap_year' => 'nullable|boolean',
            'perm_village' => 'nullable|string|max:100',
            'perm_thana' => 'nullable|string|max:100',
            'perm_post' => 'nullable|string|max:100',
            'perm_district' => 'nullable|string|max:100',
            'perm_state' => 'nullable|string|max:100',
            'perm_pincode' => ['nullable', ...$this->strictPincodeRules()],
            'perm_address' => 'nullable|string|max:255',
            'comm_address' => 'nullable|string|max:255',
            'comm_thana' => 'nullable|string|max:100',
            'comm_post' => 'nullable|string|max:100',
            'comm_city' => 'nullable|string|max:100',
            'comm_district' => 'nullable|string|max:100',
            'comm_state' => 'nullable|string|max:100',
            'comm_pincode' => ['nullable', ...$this->strictPincodeRules()],
            'course_type_id'   => ['required', 'integer', \Illuminate\Validation\Rule::exists('course_types', 'id')->where('institute_id', $this->instituteId())],
            'course_stream_id' => 'required|integer|exists:course_streams,id',
            'course_part_id'   => 'nullable|integer|exists:course_parts,id',
            'selected_subjects' => 'nullable|array',
            'selected_subjects.*' => 'integer|exists:subjects,id',
            'selected_major_subjects' => 'nullable|array',
            'selected_major_subjects.*' => 'integer|exists:subjects,id',
            'selected_minor_subjects' => 'nullable|array',
            'selected_minor_subjects.*' => 'integer|exists:subjects,id',
            'education' => 'nullable|array',
            'education.*.exam_name' => 'nullable|string|max:50',
            'education.*.institute_name' => 'nullable|string|max:150',
            'education.*.education_stream' => 'nullable|string|max:50',
            'education.*.roll_number' => 'nullable|string|max:50',
            'education.*.passing_year' => ['nullable', ...$this->passingYearRules()],
            'education.*.district' => 'nullable|string|max:100',
            'education.*.division' => 'nullable|string|max:20',
            'education.*.board_university' => 'nullable|string|max:150',
            'education.*.obtained_marks' => 'nullable|numeric|min:0',
            'education.*.max_marks' => 'nullable|numeric|gt:0',
            'education.*.percentage' => 'nullable|numeric|min:0|max:100',
            'has_scholarship' => 'nullable|boolean',
            'scholarship_name' => 'nullable|string|max:100',
            'scholarship_type' => 'nullable|string|max:50',
            'scholarship_authority' => 'nullable|string|max:100',
            'scholarship_applied_date' => 'nullable|date',
            'scholarship_amount' => 'nullable|numeric|min:0',
            'scholarship_ref_no' => 'nullable|string|max:100',
            'photo'              => 'nullable|image|max:2048',
            'fee_plan_id'        => ['nullable', Rule::exists('fee_plans', 'id')->where('institute_id', $this->instituteId())],
        ]);
        $this->attachAdmissionCrossFieldValidation($validator, $request->all());
        $validated = $validator->validate();

        $stream = CourseStream::with('course')->findOrFail((int) $validated['course_stream_id']);
        if ((int) $stream->course?->institute_id !== (int) $this->instituteId()) {
            abort(403);
        }

        $selectedPart = $this->resolveCoursePartForEdit(
            $stream,
            !empty($validated['course_part_id']) ? (int) $validated['course_part_id'] : null,
            $student
        );

        if (!$selectedPart) {
            throw ValidationException::withMessages([
                'course_part_id' => 'No valid course part found for the selected stream.',
            ]);
        }

        $subjectSelection = $this->normalizeSubjectSelection(
            (int) $stream->id,
            (int) $selectedPart->year_number,
            $validated['selected_major_subjects'] ?? [],
            $validated['selected_minor_subjects'] ?? [],
            $validated['selected_subjects'] ?? []
        );

        $oldSnapshot = StudentAcademicChangeService::buildSnapshot($student);

        DB::transaction(function () use ($request, $validated, $student, $stream, $selectedPart, $subjectSelection, $oldSnapshot) {
            $sessionId = (int) $student->academic_session_id;
            $oldYearNumber = (int) ($oldSnapshot['course_part_year'] ?? ($student->coursePart?->year_number ?? 1));
            $oldRoleMap = StudentAcademicChangeService::currentRoleMap($student, $sessionId, $oldYearNumber);

            $updateData = [
                'name'                => $validated['name'],
                'mobile'              => $validated['mobile'] ?? null,
                'email'               => $request->input('email', $student->email),
                'dob'                 => $request->input('dob', $student->dob),
                'gender'              => $request->input('gender', $student->gender),
                'religion'            => $request->input('religion', $student->religion),
                'category'            => $request->input('category', $student->category),
                'special_category'    => $request->exists('special_category')
                    ? $this->normalizeSpecialCategoryValue($validated['special_category'] ?? null)
                    : $student->special_category,
                'has_scholarship'     => (bool) ($validated['has_scholarship'] ?? false),
                'scholarship_name'    => !empty($validated['has_scholarship']) ? ($validated['scholarship_name'] ?? null) : null,
                'scholarship_type'    => !empty($validated['has_scholarship']) ? ($validated['scholarship_type'] ?? null) : null,
                'scholarship_authority' => !empty($validated['has_scholarship']) ? ($validated['scholarship_authority'] ?? null) : null,
                'scholarship_applied_date' => !empty($validated['has_scholarship']) ? ($validated['scholarship_applied_date'] ?? null) : null,
                'scholarship_amount'  => !empty($validated['has_scholarship']) ? ($validated['scholarship_amount'] ?? null) : null,
                'scholarship_ref_no'  => !empty($validated['has_scholarship']) ? ($validated['scholarship_ref_no'] ?? null) : null,
                'nationality'         => $request->exists('nationality')
                    ? $this->normalizeNationalityValue($validated['nationality'] ?? null)
                    : $student->nationality,
                'student_type'        => $request->input('student_type', $student->student_type),
                'marital_status'      => $request->input('marital_status', $student->marital_status),
                'aadhar_no'           => $request->input('aadhar_no', $student->aadhar_no),
                'apaar_no'            => $request->input('apaar_no', $student->apaar_no),
                'father_name'         => $request->input('father_name', $student->father_name),
                'father_mobile'       => $request->input('father_mobile', $student->father_mobile),
                'father_occupation'   => $request->input('father_occupation', $student->father_occupation),
                'mother_name'         => $request->input('mother_name', $student->mother_name),
                'mother_mobile'       => $request->input('mother_mobile', $student->mother_mobile),
                'mother_occupation'   => $request->input('mother_occupation', $student->mother_occupation),
                'guardian_name'       => $request->input('guardian_name', $student->guardian_name),
                'guardian_mobile'     => $request->input('guardian_mobile', $student->guardian_mobile),
                'guardian_relation'   => $request->input('guardian_relation', $student->guardian_relation),
                'admission_type'      => $request->input('admission_type', $student->admission_type),
                'admission_source'    => $request->input('admission_source', $student->admission_source),
                'admission_source_id' => $request->input('admission_source_id', $student->admission_source_id),
                'admission_date'      => $request->input('admission_date', $student->admission_date),
                'submitted_date'      => $student->submitted_date,
                'sr_no'               => $request->input('sr_no', $student->sr_no),
                'enrollment_no'       => $request->input('enrollment_no', $student->enrollment_no),
                'roll_no'             => $request->input('roll_no', $student->roll_no),
                'exam_form_no'        => $request->input('exam_form_no', $student->exam_form_no),
                'institute_form_no'   => $request->input('institute_form_no', $student->institute_form_no),
                'uin_no'              => $request->input('uin_no', $student->uin_no),
                'reference_no'        => $request->input('reference_no', $student->reference_no),
                'gap_year'            => (bool) ($validated['gap_year'] ?? $student->gap_year),
                'perm_village'        => $request->input('perm_village', $student->perm_village),
                'perm_thana'          => $request->input('perm_thana', $student->perm_thana),
                'perm_post'           => $request->input('perm_post', $student->perm_post),
                'perm_district'       => $request->input('perm_district', $student->perm_district),
                'perm_state'          => $request->input('perm_state', $student->perm_state),
                'perm_pincode'        => $request->input('perm_pincode', $student->perm_pincode),
                'perm_address'        => $request->input('perm_address', $student->perm_address),
                'comm_address'        => $request->input('comm_address', $student->comm_address),
                'comm_thana'          => $request->input('comm_thana', $student->comm_thana),
                'comm_post'           => $request->input('comm_post', $student->comm_post),
                'comm_city'           => $request->input('comm_city', $student->comm_city),
                'comm_district'       => $request->input('comm_district', $student->comm_district),
                'comm_state'          => $request->input('comm_state', $student->comm_state),
                'comm_pincode'        => $request->input('comm_pincode', $student->comm_pincode),
                'course_type_id'      => $request->input('course_type_id', $student->course_type_id),
                'course_stream_id'    => $stream->id,
                'course_part_id'      => $selectedPart->id,
                'fee_plan_id'         => $request->exists('fee_plan_id')
                    ? (!empty($validated['fee_plan_id']) ? (int) $validated['fee_plan_id'] : null)
                    : $student->fee_plan_id,
            ];

            if ($request->hasFile('photo')) {
                if ($student->photo) {
                    Storage::disk('public')->delete($student->photo);
                }
                $updateData['photo'] = $request->file('photo')->store('students/photos', 'public');
            }

            $student->update($updateData);

            if ($request->has('education')) {
                $student->educationDetails()->delete();

                foreach ($this->normalizeEducationRows($validated['education'] ?? []) as $edu) {
                    $hasData = collect([
                        $edu['institute_name'] ?? null,
                        $edu['education_stream'] ?? null,
                        $edu['roll_number'] ?? null,
                        $edu['passing_year'] ?? null,
                        $edu['district'] ?? null,
                        $edu['division'] ?? null,
                        $edu['board_university'] ?? null,
                        $edu['obtained_marks'] ?? null,
                        $edu['max_marks'] ?? null,
                        $edu['percentage'] ?? null,
                    ])->contains(fn($value) => filled($value));

                    if (!$hasData) {
                        continue;
                    }

                    $student->educationDetails()->create([
                        'exam_name' => $edu['exam_name'] ?? null,
                        'institute_name' => $edu['institute_name'] ?? null,
                        'education_stream' => $edu['education_stream'] ?? null,
                        'board_university' => $edu['board_university'] ?? null,
                        'roll_number' => $edu['roll_number'] ?? null,
                        'passing_year' => $edu['passing_year'] ?? null,
                        'district' => $edu['district'] ?? null,
                        'division' => $edu['division'] ?? null,
                        'obtained_marks' => $edu['obtained_marks'] ?? null,
                        'max_marks' => $edu['max_marks'] ?? null,
                        'percentage' => $edu['percentage'] ?? null,
                    ]);
                }
            }

            $student->load(['stream.course', 'coursePart']);

            $yearNumber = (int) $selectedPart->year_number;

            $finalSubjectIds = StudentAcademicChangeService::syncSubjects(
                $student,
                $sessionId,
                $yearNumber,
                $subjectSelection['rows']
            );

            // Build new role map from submitted selection rows
            $newRoleMap = [];
            foreach ($subjectSelection['rows'] as $row) {
                $sid = (int) ($row['subject_id'] ?? 0);
                if ($sid > 0) {
                    $newRoleMap[$sid] = (string) ($row['subject_role'] ?? 'minor');
                }
            }

            $newSnapshot = StudentAcademicChangeService::buildSnapshot(
                $student,
                $stream->id,
                $selectedPart->id,
                $finalSubjectIds,
                [
                    'student_type' => $student->student_type,
                    'admission_source' => $student->admission_source,
                    'category' => $student->category,
                    'gender' => $student->gender,
                ]
            );

            $actor     = $this->authenticatedUser();
            $actorType = $this->actorType() ?? 'web';
            $actorName = $actor?->name ?? $actor?->email ?? ('User #' . ($actor?->id ?? 0));

            $adjustment = StudentAcademicChangeService::applySubjectChange(
                $student,
                $oldSnapshot,
                $newSnapshot,
                $oldRoleMap,
                $newRoleMap,
                $actor?->id,
                $actorType,
                $actorName
            );

            StudentAcademicChangeService::syncCurrentIdentity($student, $finalSubjectIds);

            StudentAcademicChangeService::createChangeLog(
                $student,
                $oldSnapshot,
                $newSnapshot,
                $adjustment,
                $actorType,
                $actorName,
                'Course / subject correction from student edit form'
            );
        });

        return redirect()->route($this->admissionRoute('show'), $student->id)
            ->with('success', 'Student profile updated successfully!');
    }

    // ─── Quick Admission ───────────────────────────────────────────────
    public function quickCreate()
    {
        $instituteId   = $this->instituteId();

        // Clear previous session — naya form blank hona chahiye
        session()->forget(['quick_admission_preview', 'quickPreviewData']);

        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();

        $formConfig     = AdmissionFormController::getActiveConfig($instituteId, 'quick');
        $quickFormConfig = AdmissionFormController::getFormConfig($instituteId, 'quick');
        $courses    = Course::where('institute_id', $instituteId)
                        ->where('status', true)
                        ->when($this->currentStaff()?->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('id', $this->currentStaff()->allowedCourseIds() ?: [-1]))
                        ->with(['streams', 'parts', 'type'])
                        ->get();
        $courseTypes  = CourseType::forInstitute($instituteId)->active()->orderBy('sort_order')->orderBy('name')->get();
        $studentTypes = StudentTypeController::getActiveTypes($instituteId);
        $centers  = Center::where('institute_id', $instituteId)->where('status', true)->get();
        $partners = ChannelPartner::where('institute_id', $instituteId)->where('status', true)->get();
        $allowedPaymentModes = $this->allowedPaymentModes();
        $allowedBankIds = $this->allowedBankAccountIds();
        $bankAccounts = $this->allowedBankAccounts($instituteId, $allowedPaymentModes, $allowedBankIds);
        $bankModeOverride = $allowedBankIds !== null ? implode(',', $allowedPaymentModes) : null;

        $admissibleSessions = $this->admissibleSessions($instituteId);

        $feePlans = FeePlan::with('installments')
            ->where('institute_id', $instituteId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view($this->quickCreateView(),
            compact(
                'activeSession',
                'admissibleSessions',
                'formConfig',
                'quickFormConfig',
                'courses',
                'courseTypes',
                'studentTypes',
                'centers',
                'partners',
                'allowedPaymentModes',
                'bankAccounts',
                'bankModeOverride',
                'feePlans'
            ));
    }

    // ── Step 1: Form submit → Session save → Quick Preview ─────────────
    public function quickStore(Request $request)
    {
        $instituteId = $this->instituteId();
        $formConfig = AdmissionFormController::getActiveConfig($instituteId, 'quick');
        $this->ensureStaffCanAccessCourseSelection(
            $request->filled('course_id') ? (int) $request->course_id : null,
            $request->filled('course_stream_id') ? (int) $request->course_stream_id : null
        );

        // Save photo to temp BEFORE validation so old('photo_temp') survives any back()->withInput() redirect
        if ($request->hasFile('photo') && !$request->filled('photo_temp')) {
            $tempPath = $request->file('photo')->store('students/photos/temp', 'public');
            $request->merge(['photo_temp' => $tempPath]);
        }

        $this->makeAdmissionValidator($request, $formConfig, true)->validate();

        $firstPart = $this->firstCoursePartForStream((int) $request->course_stream_id);
        if ($firstPart) {
            $request->merge([
                'course_part_id' => $firstPart->id,
                'semester' => 1,
            ]);
        }

        $activeSession = $this->resolveAdmissionSession(
            $instituteId,
            $request->filled('session_id') ? (int) $request->session_id : null
        );

        $allowedPaymentModes = $this->allowedPaymentModes();
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

        // Transaction ref mandatory for non-cash payments
        if ($request->payment_mode !== 'cash' && !$request->filled('transaction_ref')) {
            return back()->withErrors([
                'transaction_ref' => 'Transaction Ref / UTR / Cheque No. required for non-cash payments.',
            ])->withInput();
        }

        // Payment datetime mandatory for non-cash (actual payment time)
        if ($request->payment_mode !== 'cash' && !$request->filled('payment_datetime')) {
            return back()->withErrors([
                'payment_datetime' => 'Payment Date & Time required for non-cash payments.',
            ])->withInput();
        }

        $validItems = collect($request->fee_items)
            ->map(function ($item) {
                return [
                    'checked'     => isset($item['checked']) ? 1 : 0,
                    'item_key'    => trim((string) ($item['item_key'] ?? '')),
                    'fee_type_id' => $item['fee_type_id'] ?? null,
                    'subject_id'  => $item['subject_id'] ?? null,
                    'item_type'   => trim((string) ($item['item_type'] ?? '')),
                    'fee_name'    => trim((string) ($item['fee_name'] ?? '')),
                    'amount'      => max(0, (float) ($item['amount'] ?? 0)),
                    'discount'    => max(0, (float) ($item['discount'] ?? 0)),
                    'fine'        => max(0, (float) ($item['fine'] ?? 0)),
                    'total_fee'   => max(0, (float) ($item['total_fee'] ?? 0)),
                    'is_custom'   => !empty($item['is_custom']) ? 1 : 0,
                ];
            })
            ->filter(fn($item) => $item['checked'] && ($item['amount'] > 0 || $item['discount'] > 0 || $item['fine'] > 0))
            ->values();

        if ($validItems->isEmpty()) {
            return back()->withErrors([
                'fee_items' => 'Please select at least one fee item.',
            ])->withInput();
        }

        $availableItems = $this->buildQuickCollectableItemMap($request, $activeSession);
        $validItems = $validItems->map(function (array $item) use ($availableItems) {
            if ($item['is_custom']) {
                return $item;
            }

            $serverItem = $availableItems[$item['item_key']] ?? null;
            if (!$serverItem && empty($item['item_key'])) {
                $serverItem = collect($availableItems)->firstWhere('fee_name', $item['fee_name']);
            }

            if (!$serverItem) {
                throw ValidationException::withMessages([
                    'fee_items' => 'One or more selected fee items are invalid or no longer available.',
                ]);
            }

            $item['fee_name'] = $serverItem['fee_name'];
            $item['fee_type_id'] = $serverItem['fee_type_id'];
            $item['subject_id'] = $serverItem['subject_id'];
            $item['item_type'] = $serverItem['item_type'];
            $item['total_fee'] = $serverItem['total_fee'];
            $item['pending_before'] = $serverItem['pending'];

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

        $totalAmount = (float) $validItems->sum('amount');
        if ($totalAmount <= 0) {
            return back()->withErrors([
                'fee_items' => 'A payment amount is required to save the admission.',
            ])->withInput();
        }

        $totalDiscount = (float) $validItems->sum('discount');

        if (auth()->guard('staff')->check() && $totalDiscount > 0) {
            $staffUser = auth()->guard('staff')->user();
            $globalLimit = $staffUser->max_discount_percent ?? 100;
            $allowedPerms = $staffUser->feeDiscountPermissions()->pluck('fee_type_id')->toArray();
            $permissionMeta = $this->feeTypePermissionMeta($allowedPerms);
            $hasPerItemConfig = count($allowedPerms) > 0;

            foreach ($validItems as $item) {
                $disc = (float) ($item['discount'] ?? 0);
                if ($disc <= 0) {
                    continue;
                }

                if ($hasPerItemConfig && !$this->isRestrictedItemAllowed($item, $allowedPerms, $permissionMeta['categories'], $permissionMeta['names'])) {
                    return back()->withErrors([
                        'fee_items' => "Discount is not allowed on '{$item['fee_name']}'.",
                    ])->withInput();
                }

                if ($globalLimit >= 100) {
                    continue;
                }

                $base = (float) ($item['total_fee'] ?? 0);
                if ($base <= 0) {
                    $base = $disc + (float) ($item['amount'] ?? 0);
                }

                if ($base > 0 && $disc > ($globalLimit / 100) * $base + 0.01) {
                    return back()->withErrors([
                        'fee_items' => "Discount on '{$item['fee_name']}' exceeds the allowed limit of {$globalLimit}%.",
                    ])->withInput();
                }
            }
        }

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
                    if ($disc <= 0) {
                        continue;
                    }

                    $base = (float) ($item['total_fee'] ?? 0);
                    if ($base <= 0) {
                        $base = $disc + (float) ($item['amount'] ?? 0);
                    }

                    if ($base > 0 && $disc > ($maxPct / 100) * $base + 0.01) {
                        return back()->withErrors([
                            'fee_items' => "Discount on '{$item['fee_name']}' exceeds the allowed limit of {$maxPct}%.",
                        ])->withInput();
                    }
                }
            }
        }

        if ($totalDiscount > 0 && auth()->guard('center')->check()) {
            $centerUser = auth()->guard('center')->user();
            $discAllowed = $centerUser->feeDiscountPermissions()->pluck('fee_type_id')->toArray();
            $permissionMeta = $this->feeTypePermissionMeta($discAllowed);

            if (count($discAllowed) > 0) {
                foreach ($validItems as $item) {
                    if ((float) ($item['discount'] ?? 0) <= 0) {
                        continue;
                    }

                    if (!$this->isRestrictedItemAllowed($item, $discAllowed, $permissionMeta['categories'], $permissionMeta['names'])) {
                        return back()->withErrors([
                            'fee_items' => "Discount is not allowed on '{$item['fee_name']}'.",
                        ])->withInput();
                    }
                }
            }
        }

        if (auth()->guard('center')->check()) {
            $centerUser = auth()->guard('center')->user();
            if ($centerUser->hasRestrictedFeeCollectionTypes()) {
                $allowedTypeIds = $centerUser->allowedFeeCollectionTypeIds();
                $permissionMeta = $this->feeTypePermissionMeta($allowedTypeIds);

                foreach ($validItems as $item) {
                    if (!empty($item['is_custom'])) {
                        continue;
                    }

                    if (!$this->isRestrictedItemAllowed($item, $allowedTypeIds, $permissionMeta['categories'], $permissionMeta['names'])) {
                        return back()->withErrors([
                            'fee_items' => "Collection is not allowed on '{$item['fee_name']}'.",
                        ])->withInput();
                    }
                }
            }
        }

        $streamId = (int) $request->course_stream_id;
        $seatCheck = \App\Http\Controllers\Institute\Master\CourseStreamController::checkSeatAvailability(
            $streamId,
            $activeSession->id
        );
        if (!$seatCheck['available']) {
            return back()->withErrors([
                'course_stream_id' => "Seats full. This stream has reached its limit of {$seatCheck['limit']} seats.",
            ])->withInput();
        }

        $year = StudentIdService::getYearFromSession($activeSession->name);
        // Non-cash: payment_date = today (receipt generation date), cash: use form-submitted date
        $paymentDate = ($this->shouldLockPaymentDate() || $request->payment_mode !== 'cash')
            ? now()->toDateString()
            : $request->payment_date;

        // For non-cash: store actual payment datetime for audit
        $paymentDatetimeNote = null;
        $paymentDatetime = null;
        if ($request->payment_mode !== 'cash' && $request->filled('payment_datetime')) {
            try {
                $dt = \Carbon\Carbon::parse($request->payment_datetime)->setTimezone('Asia/Kolkata');
                $paymentDatetime = $dt;
                $paymentDatetimeNote = 'Payment received: ' . $dt->format('d M Y, h:i A');
            } catch (\Exception $e) {
                return back()->withErrors([
                    'payment_datetime' => 'Payment date & time is invalid. Please enter a valid date and time.',
                ])->withInput();
            }
        }

        $yearNumber = $firstPart?->year_number ?? 1;

        $rawSubjectIds = array_values(array_filter(array_unique(array_map(
            'intval',
            array_filter($request->input('selected_subjects', []), fn($v) => $v !== null && $v !== '')
        ))));

        $subjectSelection = $this->normalizeSubjectSelection(
            $streamId,
            $yearNumber,
            $request->input('selected_major_subjects', []),
            $request->input('selected_minor_subjects', []),
            $rawSubjectIds,
            true // subjects are optional in quick registration
        );
        $selectedSubjectIds = $subjectSelection['subject_ids'];

        $studentId = null;
        $invoiceNo  = null;
        $student    = null;
        $invoiceId  = null;

        try {
        DB::transaction(function () use (
            $request,
            $instituteId,
            $activeSession,
            $year,
            $paymentDate,
            $paymentDatetime,
            $paymentDatetimeNote,
            $subjectSelection,
            $selectedSubjectIds,
            $yearNumber,
            $validItems,
            $selectedBankAccount,
            $totalAmount,
            $totalDiscount,
            &$studentId,
            &$invoiceNo,
            &$student,
            &$invoiceId
        ) {
            $studentId = StudentIdService::generateStudentId($instituteId, $year);
            $invoiceNo = StudentIdService::generateInvoiceId($instituteId, $year);
            $student = Student::create([
                'institute_id'        => $instituteId,
                'academic_session_id' => $activeSession->id,
                'student_uid'         => $studentId,
                'name'                => $request->name,
                'mobile'              => $request->mobile ?? null,
                'gender'              => $request->gender ?? null,
                'dob'                 => $request->dob ?? null,
                'email'               => $request->email ?? null,
                'category'            => $request->category ?? null,
                'special_category'    => $this->normalizeSpecialCategoryValue($request->special_category ?? null),
                'nationality'         => $this->normalizeNationalityValue($request->nationality ?? null),
                'religion'            => $request->religion ?? null,
                'student_type'        => $request->student_type ?? 'regular',
                'marital_status'      => $request->marital_status ?? 'single',
                'aadhar_no'           => $request->aadhar_no ?? null,
                'apaar_no'            => $request->apaar_no ?? null,
                'father_name'         => $request->father_name ?? null,
                'father_mobile'       => $request->father_mobile ?? null,
                'mother_name'         => $request->mother_name ?? null,
                'guardian_mobile'     => $request->guardian_mobile ?? null,
                'institute_form_no'   => $request->institute_form_no ?? null,
                'sr_no'               => $request->sr_no ?? null,
                'enrollment_no'       => $request->enrollment_no ?? null,
                'roll_no'             => $request->roll_no ?? null,
                'exam_form_no'        => $request->exam_form_no ?? null,
                'uin_no'              => $request->uin_no ?? null,
                'reference_no'        => $request->reference_no ?? null,
                'gap_year'            => (bool) ($request->gap_year ?? false),
                'admission_type'      => $request->admission_type ?? 'new',
                'admission_source'    => $request->admission_source ?? 'direct',
                'admission_source_id' => $request->admission_source_id ?? null,
                'admission_date'      => $request->admission_date ?? now()->toDateString(),
                'submitted_date'      => now()->toDateString(),
                'perm_village'        => $request->perm_village ?? null,
                'perm_post'           => $request->perm_post ?? null,
                'perm_thana'          => $request->perm_thana ?? null,
                'perm_district'       => $request->perm_district ?? null,
                'perm_state'          => $request->perm_state ?? null,
                'perm_pincode'        => $request->perm_pincode ?? null,
                'comm_address'        => $request->comm_address ?? null,
                'course_type_id'      => $request->course_type_id ?? null,
                'course_stream_id'    => $request->course_stream_id,
                'course_part_id'      => $request->course_part_id ?? null,
                'current_semester'    => 1,
                'status'              => $this->initialAdmissionStatus(),
                'admitted_by_staff_id' => auth()->guard('staff')->check() ? auth()->guard('staff')->id() : null,
                'is_quick_admission'  => true,
                'has_scholarship'     => (bool) ($request->has_scholarship ?? false),
                'scholarship_name'    => ($request->has_scholarship ?? false) ? ($request->scholarship_name ?? null) : null,
                'scholarship_type'    => ($request->has_scholarship ?? false) ? ($request->scholarship_type ?? null) : null,
                'scholarship_authority' => ($request->has_scholarship ?? false) ? ($request->scholarship_authority ?? null) : null,
                'scholarship_applied_date' => ($request->has_scholarship ?? false) ? ($request->scholarship_applied_date ?? null) : null,
                'scholarship_amount'  => ($request->has_scholarship ?? false) ? ($request->scholarship_amount ?? null) : null,
                'scholarship_ref_no'  => ($request->has_scholarship ?? false) ? ($request->scholarship_ref_no ?? null) : null,
            ]);

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('students/photos', 'public');
                $student->update(['photo' => $photoPath]);
            }

            foreach ($this->normalizeEducationRows($request->input('education', [])) as $edu) {
                if (empty($edu['exam_name'])) {
                    continue;
                }

                $student->educationDetails()->create([
                    'exam_name'        => $edu['exam_name'],
                    'institute_name'   => $edu['institute_name'] ?? null,
                    'education_stream' => $edu['education_stream'] ?? null,
                    'board_university' => $edu['board_university'] ?? null,
                    'roll_number'      => $edu['roll_number'] ?? null,
                    'passing_year'     => $edu['passing_year'] ?? null,
                    'district'         => $edu['district'] ?? null,
                    'division'         => $edu['division'] ?? null,
                    'obtained_marks'   => $edu['obtained_marks'] ?? null,
                    'max_marks'        => $edu['max_marks'] ?? null,
                    'percentage'       => $edu['percentage'] ?? null,
                ]);
            }

            foreach ($subjectSelection['rows'] as $row) {
                StudentSubject::firstOrCreate(
                    [
                        'student_id'          => $student->id,
                        'subject_id'          => $row['subject_id'],
                        'academic_session_id' => $activeSession->id,
                        'year_number'         => $yearNumber,
                    ],
                    [
                        'subject_role'     => $row['subject_role'],
                        'is_auto_included' => $row['is_auto_included'],
                    ]
                );
            }

            WalletService::onAdmission($student);

            \App\Models\StudentAcademicIdentity::firstOrCreate(
                [
                    'student_id'          => $student->id,
                    'academic_session_id' => $student->academic_session_id,
                ],
                [
                    'institute_id'              => $student->institute_id,
                    'course_id'                 => $student->stream->course_id ?? null,
                    'course_stream_id'          => $student->course_stream_id,
                    'course_part_id'            => $student->course_part_id,
                    'semester_at_time'          => $student->current_semester,
                    'subjects_json'             => $selectedSubjectIds,
                    'form_no'                   => last(explode('/', $studentId)),
                    'sr_no_snapshot'            => $student->sr_no,
                    'enrollment_no_snapshot'    => $student->enrollment_no,
                    'roll_no_snapshot'          => $student->roll_no,
                    'admission_source_snapshot' => $student->admission_source,
                    'source'                    => 'admission',
                    'admission_type'            => $student->admission_type ?? 'new',
                ]
            );

            $invoice = \App\Models\FeeInvoice::create([
                'institute_id'        => $instituteId,
                'student_id'          => $student->id,
                'academic_session_id' => $student->academic_session_id,
                'semester'            => $request->semester,
                'invoice_no'          => $invoiceNo,
                'total_amount'        => $totalAmount + $totalDiscount,
                'discount'            => $totalDiscount,
                'paid_amount'         => $totalAmount,
                'payment_mode'        => $request->payment_mode,
                'bank_account_id'     => $selectedBankAccount?->id,
                'transaction_ref'     => $request->transaction_ref,
                'bank_name'           => $request->bank_name,
                'payment_date'        => $paymentDate,
                'payment_datetime'    => $paymentDatetime,
                'remarks'             => $request->remarks ?: null,
                'collected_by'        => $this->authenticatedUser()?->name,
                'collected_by_staff_id' => auth()->guard('staff')->id(),
                'collected_by_center_id' => auth()->guard('center')->id(),
                'collected_by_partner_id' => auth()->guard('partner')->id(),
            ]);

            $invoice->load('student');

            $wallet = $this->portalWallet();
            if ($wallet && $totalAmount > 0) {
                $wallet->consumeOrFail($totalAmount, $invoice->id, $this->actorId());
            }

            foreach ($validItems as $item) {
                $feeType = !empty($item['fee_type_id'])
                    ? \App\Models\FeeType::find($item['fee_type_id'])
                    : null;
                $fine        = (float) ($item['fine'] ?? 0);
                $collected   = (float) ($item['amount'] ?? 0);
                $discount    = (float) ($item['discount'] ?? 0);
                // For custom rows, collect is actual cash received; discount is an extra settlement credit.
                $assignedFee = (float) ($item['total_fee'] ?? 0);
                if (!empty($item['is_custom']) || $assignedFee <= 0) {
                    $assignedFee = $collected + $discount;
                }

                \App\Models\FeeInvoiceItem::create([
                    'fee_invoice_id' => $invoice->id,
                    'fee_type_id'    => $feeType?->id,
                    'subject_id'     => !empty($item['subject_id']) ? (int) $item['subject_id'] : null,
                    'item_type'      => !empty($item['item_type']) ? (string) $item['item_type'] : null,
                    'fee_name'       => $item['fee_name'] ?? ($feeType?->name ?? 'Fee'),
                    'amount'         => $collected,
                    'discount'       => $discount,
                    'fine'           => $fine,
                    'total_fee'      => $assignedFee,
                ]);
            }

            WalletService::chargeCustomFeeItems($invoice, $validItems);
            WalletService::chargeFineItems($invoice, $validItems);
            WalletService::onFeeCollection($invoice);

            if (auth()->guard('partner')->check()) {
                $partner = auth()->guard('partner')->user();
                $commissionPercent = (float) ($partner->commission_percent ?? 0);
                if ($commissionPercent > 0 && $totalAmount > 0) {
                    \App\Models\PartnerCommissionEntry::updateOrCreate(
                        ['fee_invoice_id' => $invoice->id],
                        [
                            'partner_id' => $partner->id,
                            'paid_amount' => $totalAmount,
                            'commission_percent' => $commissionPercent,
                            'commission_amount' => round(($totalAmount * $commissionPercent) / 100, 2),
                        ]
                    );
                }
            }

            $invoiceId = $invoice->id;
        });
        } catch (DomainException $e) {
            return back()->withErrors([
                'wallet_error' => $e->getMessage(),
            ])->withInput();
        }

        AuditLogService::log($instituteId, 'admission', 'admission_confirmed_with_fee', 'Admission completed with fee collection.', $student, [
            'student_id' => $student->id,
            'invoice_id' => $invoiceId,
            'invoice_no' => $invoiceNo,
        ]);

        return redirect()->route($this->admissionRoute('print-all-receipt'), [
            'student' => $student->id,
            'invoice' => $invoiceId,
        ])->with('success', "Admission successful! Invoice: {$invoiceNo}");
    }

    // ── Quick Preview — Edit pe wapas form mein session data se ────────
    public function quickEditPreview()
    {
        $formData = session('quick_admission_preview');
        if (!$formData) {
            return redirect()->route($this->admissionRoute('quick-create'));
        }

        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();
        $formConfig      = AdmissionFormController::getActiveConfig($instituteId, 'quick');
        $quickFormConfig = AdmissionFormController::getFormConfig($instituteId, 'quick');
        $courses         = Course::where('institute_id', $instituteId)->where('status', true)
                              ->when($this->currentStaff()?->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('id', $this->currentStaff()->allowedCourseIds() ?: [-1]))
                              ->with(['streams', 'parts'])->get();
        $centers         = Center::where('institute_id', $instituteId)->where('status', true)->get();
        $partners        = ChannelPartner::where('institute_id', $instituteId)->where('status', true)->get();

        // Session mein store + direct view variable bhi pass karo
        session()->put('quickPreviewData', $formData);

        return view($this->quickCreateView(), compact(
            'activeSession', 'formConfig', 'quickFormConfig', 'courses', 'centers', 'partners'
        ))->with('qd', $formData); // Direct PHP se pass — JS pe depend nahi
    }

    // ── Step 2: Quick Preview confirm → DB save → Fee Payment ───────────
    public function quickConfirm(Request $request)
    {
        $instituteId = $this->instituteId();
        $formData    = session('quick_admission_preview');

        if (!$formData) {
            return redirect()->route($this->admissionRoute('quick-create'))
                ->with('error', 'Session expired. Please fill the form again.');
        }
        $this->ensureStaffCanAccessCourseSelection(
            isset($formData['course_id']) ? (int) $formData['course_id'] : null,
            isset($formData['course_stream_id']) ? (int) $formData['course_stream_id'] : null
        );

        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->firstOrFail();

        // ── Seat availability check ──────────────────────────────────
        $streamId  = $formData['course_stream_id'] ?? null;
        if ($streamId) {
            $seatCheck = \App\Http\Controllers\Institute\Master\CourseStreamController::checkSeatAvailability(
                (int) $streamId, $activeSession->id
            );
            if (!$seatCheck['available']) {
                return redirect()->route($this->admissionRoute('quick-create'))
                    ->with('error', "❌ Seats Full! This stream had {$seatCheck['limit']} seats which are now all filled. No new admission can be created.");
            }
        }

        $year      = StudentIdService::getYearFromSession($activeSession->name);
        $studentId = StudentIdService::generateStudentId($instituteId, $year);

        $firstPart = $this->firstCoursePartForStream((int) ($formData['course_stream_id'] ?? 0));
        if ($firstPart) {
            $formData['course_part_id'] = $firstPart->id;
            $formData['semester'] = 1;
        }

        $selectedSubjectIds = array_values(array_filter(array_map(
            'intval',
            $formData['selected_subjects'] ?? []
        )));
        $student = null;

        DB::transaction(function () use (
            $formData, $instituteId, $activeSession, $studentId, $selectedSubjectIds, &$student
        ) {
            $student = Student::create([
                'institute_id'        => $instituteId,
                'academic_session_id' => $activeSession->id,
                'student_uid'         => $studentId,
                'name'                => $this->normalizeUppercaseString($formData['name']),
                'mobile'              => $formData['mobile'] ?? null,
                'gender'              => $formData['gender']            ?? null,
                'dob'                 => $formData['dob']               ?? null,
                'email'               => $formData['email']             ?? null,
                'institute_form_no'   => $this->normalizeUppercaseString($formData['institute_form_no'] ?? null),
                'sr_no'               => $this->normalizeUppercaseString($formData['sr_no'] ?? null),
                'enrollment_no'       => $this->normalizeUppercaseString($formData['enrollment_no'] ?? null),
                'roll_no'             => $this->normalizeUppercaseString($formData['roll_no'] ?? null),
                'exam_form_no'        => $this->normalizeUppercaseString($formData['exam_form_no'] ?? null),
                'uin_no'              => $this->normalizeUppercaseString($formData['uin_no'] ?? null),
                'reference_no'        => $this->normalizeUppercaseString($formData['reference_no'] ?? null),
                'category'            => $formData['category']          ?? null,
                'special_category'    => $this->normalizeSpecialCategoryValue($formData['special_category'] ?? null),
                'nationality'         => $this->normalizeUppercaseString($this->normalizeNationalityValue($formData['nationality'] ?? null)),
                'religion'            => $formData['religion']           ?? null,
                'student_type'        => $formData['student_type']       ?? 'regular',
                'marital_status'      => $formData['marital_status']     ?? 'single',
                'aadhar_no'           => $formData['aadhar_no']          ?? null,
                'apaar_no'            => $formData['apaar_no']           ?? null,
                'father_name'         => $this->normalizeUppercaseString($formData['father_name'] ?? null),
                'father_mobile'       => $formData['father_mobile']      ?? null,
                'mother_name'         => $this->normalizeUppercaseString($formData['mother_name'] ?? null),
                'guardian_mobile'     => $formData['guardian_mobile']    ?? null,
                'admission_type'      => $formData['admission_type']     ?? 'new',
                'admission_source'    => $formData['admission_source']   ?? 'direct',
                'admission_source_id' => $formData['admission_source_id'] ?? null,
                'gap_year'            => (bool) ($formData['gap_year'] ?? false),
                'admission_date'      => $formData['admission_date']     ?? now()->toDateString(),
                'submitted_date'      => now()->toDateString(),
                'perm_village'        => $formData['perm_village']       ?? null,
                'perm_post'           => $formData['perm_post']          ?? null,
                'perm_thana'          => $formData['perm_thana']         ?? null,
                'perm_district'       => $formData['perm_district']      ?? null,
                'perm_state'          => $formData['perm_state']         ?? null,
                'perm_pincode'        => $formData['perm_pincode']       ?? null,
                'comm_address'        => $formData['comm_address']       ?? null,
                'course_type_id'      => $formData['course_type_id']     ?? null,
                'course_stream_id'    => $formData['course_stream_id'],
                'course_part_id'      => $firstPart?->id ?? ($formData['course_part_id'] ?? null),
                'current_semester'    => 1,
                'status'              => $this->initialAdmissionStatus(),
                'admitted_by_staff_id' => auth()->guard('staff')->check() ? auth()->guard('staff')->id() : null,
                'has_scholarship'     => (bool) ($formData['has_scholarship'] ?? false),
                'scholarship_name'    => ($formData['has_scholarship'] ?? false) ? ($formData['scholarship_name'] ?? null) : null,
                'scholarship_type'    => ($formData['has_scholarship'] ?? false) ? ($formData['scholarship_type'] ?? null) : null,
                'scholarship_authority' => ($formData['has_scholarship'] ?? false) ? ($formData['scholarship_authority'] ?? null) : null,
                'scholarship_applied_date' => ($formData['has_scholarship'] ?? false) ? ($formData['scholarship_applied_date'] ?? null) : null,
                'scholarship_amount'  => ($formData['has_scholarship'] ?? false) ? ($formData['scholarship_amount'] ?? null) : null,
                'scholarship_ref_no'  => ($formData['has_scholarship'] ?? false) ? ($formData['scholarship_ref_no'] ?? null) : null,
            ]);

            // Photo move karo temp se permanent
            if (!empty($formData['photo_temp'])) {
                $finalPath = str_replace('/temp/', '/', $formData['photo_temp']);
                \Illuminate\Support\Facades\Storage::disk('public')->move($formData['photo_temp'], $finalPath);
                $student->update(['photo' => $finalPath]);
            }

            // Education details
            foreach ($this->normalizeEducationRows($formData['education'] ?? []) as $edu) {
                if (empty($edu['exam_name'])) continue;
                $student->educationDetails()->create([
                    'exam_name'        => $edu['exam_name'],
                    'institute_name'   => $edu['institute_name']   ?? null,
                    'education_stream' => $edu['education_stream'] ?? null,
                    'board_university' => $edu['board_university'] ?? null,
                    'roll_number'      => $edu['roll_number']      ?? null,
                    'passing_year'     => $edu['passing_year']     ?? null,
                    'district'         => $edu['district']         ?? null,
                    'division'         => $edu['division']         ?? null,
                    'obtained_marks'   => $edu['obtained_marks']   ?? null,
                    'max_marks'        => $edu['max_marks']        ?? null,
                    'percentage'       => $edu['percentage']       ?? null,
                ]);
            }

            // Subjects save
            $yearNumber = $firstPart?->year_number ?? 1;

            if (!empty($selectedSubjectIds)) {
                $roles = CourseStreamSubject::where('course_stream_id', $formData['course_stream_id'])
                    ->where('year_number', $yearNumber)
                    ->where('is_active', true)
                    ->whereIn('subject_id', $selectedSubjectIds)
                    ->pluck('subject_role', 'subject_id');
                foreach ($selectedSubjectIds as $sid) {
                    StudentSubject::firstOrCreate(
                        ['student_id' => $student->id, 'subject_id' => $sid, 'academic_session_id' => $activeSession->id, 'year_number' => $yearNumber],
                        ['subject_role' => $roles[$sid] ?? 'compulsory', 'is_auto_included' => false]
                    );
                }
            }

            WalletService::onAdmission($student);

            // Academic identity create karo
            \App\Models\StudentAcademicIdentity::firstOrCreate(
                [
                    'student_id'          => $student->id,
                    'academic_session_id' => $student->academic_session_id,
                ],
                [
                    'institute_id'              => $student->institute_id,
                    'course_id'                 => $student->stream->course_id ?? null,
                    'course_stream_id'          => $student->course_stream_id,
                    'course_part_id'            => $student->course_part_id,
                    'semester_at_time'          => $student->current_semester,
                    'subjects_json'             => $selectedSubjectIds,
                    'form_no'                   => last(explode('/', $studentId)),
                    'sr_no_snapshot'            => $student->sr_no,
                    'enrollment_no_snapshot'    => $student->enrollment_no,
                    'roll_no_snapshot'          => $student->roll_no,
                    'admission_source_snapshot' => $student->admission_source,
                    'source'                    => 'admission',
                    'admission_type'            => $student->admission_type ?? 'new',
                ]
            );
        });

        session()->forget(['quick_admission_preview', 'quickPreviewData']);

        session(['from_admission_fee_payment' => $student->id]);

        AuditLogService::log($instituteId, 'admission', 'quick_admission_saved', 'Quick admission saved.', $student, [
            'student_id' => $student->id,
            'student_uid' => $studentId,
        ]);

        // collect_fee flag from form builder config
        $quickFormConfig = AdmissionFormController::getFormConfig($instituteId, 'quick');
        $collectFee = $quickFormConfig['collect_fee'] ?? true;

        $goToFeePayment    = $collectFee && $this->canCollectFee();
        $afterDocNextRoute = $goToFeePayment
            ? $this->admissionRoute('fee-payment')
            : $this->admissionRoute('quick-success');

        // Fee collection skipped — clear flag so pending block works correctly
        if (!$goToFeePayment) {
            session()->forget('from_admission_fee_payment');
        }

        // Check doc upload setting — intercept before next route
        $docSetting = $this->docUploadSetting('quick');
        if (in_array($docSetting, ['optional', 'required'])) {
            session([
                'doc_upload_setting' => $docSetting,
                'doc_upload_next'    => route($afterDocNextRoute, $student->id),
            ]);
            return redirect()
                ->route($this->admissionRoute('upload-documents'), $student->id)
                ->with('success', "Quick admission saved! Student ID: {$studentId}. Ab documents upload karo.");
        }

        return redirect()
            ->route($afterDocNextRoute, $student->id)
            ->with('success', "Quick admission saved! Student ID: {$studentId}");
    }

    public function quickSuccess(Student $student)
    {
        if ($student->institute_id && $student->institute_id !== $this->instituteId()) abort(403);
        $student->load(['stream.course', 'session', 'coursePart']);
        $institute   = \App\Models\Institute::find($student->institute_id);
        $lastInvoice = \App\Models\FeeInvoice::where('student_id', $student->id)
            ->where('is_cancelled', false)
            ->with('items')
            ->latest()
            ->first();
        return view('institute.admission.quick-success', compact('student', 'institute', 'lastInvoice'));
    }

    public function printForm(Student $student)
    {
        if ($student->institute_id && $student->institute_id !== $this->instituteId()) abort(403);
        $this->ensureStaffCanAccessStudent($student);
        $instituteId = $this->instituteId();
        $student->load(['stream.course', 'session', 'educationDetails', 'coursePart', 'currentAcademicIdentity']);
        $formConfig  = AdmissionFormController::getActiveConfig($instituteId, 'admission');
        $institute   = \App\Models\Institute::find($instituteId);
        $lastInvoice = \App\Models\FeeInvoice::where('student_id', $student->id)
            ->where('is_cancelled', false)
            ->with('items')
            ->latest()
            ->first();

        // Resolve admission source name (Center or Channel Partner)
        $admissionSourceName = null;
        if ($student->admission_source === 'center' && $student->admission_source_id) {
            $admissionSourceName = \App\Models\Center::find($student->admission_source_id)?->name;
        } elseif ($student->admission_source === 'channel_partner' && $student->admission_source_id) {
            $admissionSourceName = \App\Models\ChannelPartner::find($student->admission_source_id)?->name;
        }

        return view('institute.admission.print-form', compact('student', 'formConfig', 'institute', 'lastInvoice', 'admissionSourceName'));
    }

    public function receiptPrint(Student $student, $receipt)
    {
        if ($student->institute_id && $student->institute_id !== $this->instituteId()) abort(403);
        $student->load(['stream.course', 'session', 'educationDetails']);
        $instituteId   = $this->instituteId();
        $receiptConfig = AdmissionFormController::getActiveConfig($instituteId, 'receipt');

        $receiptModel = (object)[
            'invoice_id'   => 'RCPT/2026/00001',
            'amount'       => 0,
            'payment_mode' => 'cash',
            'collected_by' => $this->authenticatedUser()?->name,
            'created_at'   => now(),
        ];

        return view('institute.fee.receipt-print', [
            'student'       => $student,
            'receipt'       => $receiptModel,
            'receiptConfig' => $receiptConfig,
            'feeItems'      => [],
            'nextStudent'   => null,
        ]);
    }

    // ─── store() — resource route alias → storePreview ────────────────
    // Route::resource 'admissions' ka store() yahan aata hai
    // Hum isko preview flow pe bhej dete hain
    public function store(Request $request)
    {
        return $this->storePreview($request);
    }
}
