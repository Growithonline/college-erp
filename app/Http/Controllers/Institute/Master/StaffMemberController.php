<?php
namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use App\Models\PaymentModePermission;
use App\Models\StaffFeeDiscountPermission;
use App\Models\StaffCoursePermission;
use App\Models\StaffFeeCollectionPermission;
use App\Models\StaffPermissionOverride;
use App\Models\StaffRole;
use App\Models\FeeType;
use App\Models\Course;
use App\Models\CourseType;
use App\Models\AcademicSession;
use App\Models\Account;
use App\Models\InstituteBankAccount;
use App\Mail\StaffCredentialsMail;
use App\Services\AccountingSetupService;
use App\Services\InstituteMailer;
use App\Services\AuditLogService;
use App\Services\StaffIdService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class StaffMemberController extends Controller
{
    private function optionalTableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    private function paymentModeLabels(): array
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

    private function studentVisibilityOptions(): array
    {
        return [
            'role_based' => 'Use role default',
            'self' => 'Own students only',
            'all' => 'All accessible students',
        ];
    }

    private function sendCredentialsEmail(StaffMember $staffMember, string $plainPassword): bool
    {
        try {
            $staffMember->loadMissing('role', 'institute');

            InstituteMailer::send(
                $staffMember->institute_id,
                $staffMember->email,
                new StaffCredentialsMail($staffMember, $plainPassword)
            );

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    public function index()
    {
        $withRelations = ['role'];
        if ($this->optionalTableExists('staff_course_permissions')) {
            $withRelations[] = 'coursePermissions.course';
        }
        if ($this->optionalTableExists('staff_permission_overrides')) {
            $withRelations[] = 'permissionOverrides';
        }

        $staff = StaffMember::with($withRelations)
            ->where('institute_id', $this->instituteId())
            ->orderBy('name')->get();

        $hasCoursePermTable = $this->optionalTableExists('staff_course_permissions');
        $hasOverridesTable  = $this->optionalTableExists('staff_permission_overrides');
        $trashedCount = StaffMember::onlyTrashed()->where('institute_id', $this->instituteId())->count();

        return view('institute.master.staff.members.index', compact('staff', 'hasCoursePermTable', 'hasOverridesTable', 'trashedCount'));
    }

    public function notifyLoginIds()
    {
        $instituteId = $this->instituteId();
        $institute = \App\Models\Institute::find($instituteId);

        $members = StaffMember::where('institute_id', $instituteId)
            ->where('status', true)
            ->whereNotNull('staff_uid')
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($members as $member) {
            try {
                InstituteMailer::send($instituteId, $member->email, new \App\Mail\LoginIdNotificationMail(
                    institute: $institute,
                    recipientName: $member->name,
                    portalLabel: 'Staff Portal',
                    loginIdLabel: 'Staff ID',
                    loginId: $member->staff_uid,
                    loginUrl: route('staff.login'),
                ));
                $sent++;
            } catch (Throwable $e) {
                \Log::warning('Login-ID notification failed', ['staff_member_id' => $member->id, 'error' => $e->getMessage()]);
                $failed++;
            }
        }

        $message = "Login ID emailed to {$sent} staff member(s).";
        if ($failed > 0) {
            $message .= " {$failed} email(s) failed to send — check logs.";
        }

        return back()->with($failed > 0 ? 'error' : 'success', $message);
    }

    public function create()
    {
        AccountingSetupService::bootstrapInstitute($this->instituteId());

        $roles = StaffRole::where('institute_id', $this->instituteId())
            ->where('status', true)->orderBy('name')->get();
        $courses = Course::where('institute_id', $this->instituteId())
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'course_type_id']);
        $courseTypes = CourseType::forInstitute($this->instituteId())->active()->orderBy('sort_order')->orderBy('name')->get();
        $feeTypes = FeeType::where('institute_id', $this->instituteId())
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get();
        $expenseAccounts = Account::where('institute_id', $this->instituteId())
            ->where('type', 'expense')
            ->where('is_active', true)
            ->whereIn('code', ['3001', '3002'])
            ->orderBy('code')
            ->get();
        $bankAccounts = InstituteBankAccount::where('institute_id', $this->instituteId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $paymentModes = $this->paymentModeLabels();
        $permissionLabels = StaffRole::permissionLabels();
        $payrollCategories = ['Teaching', 'Office', 'Non-Teaching', 'Guest'];
        $sessions = AcademicSession::where('institute_id', $this->instituteId())
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();
        $selectedRoleForAccess = $roles->firstWhere('id', (int) old('staff_role_id'));
        $studentVisibilityOptions = $this->studentVisibilityOptions();

        return view('institute.master.staff.members.create', compact(
            'roles', 'expenseAccounts', 'courses', 'courseTypes', 'feeTypes', 'bankAccounts', 'paymentModes',
            'permissionLabels', 'payrollCategories', 'sessions', 'selectedRoleForAccess', 'studentVisibilityOptions'
        ));
    }

    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'staff_role_id' => ['required', Rule::exists('staff_roles', 'id')->where('institute_id', $this->instituteId())],
            'name'          => 'required|string|max:100',
            'mobile'        => 'required|digits:10',
            'email'         => ['required', 'email', Rule::unique('staff_members', 'email')->where('institute_id', $this->instituteId())->whereNull('deleted_at')],
            'joining_date'  => 'nullable|date',
            'salary'        => 'nullable|numeric|min:0',
            'staff_category' => 'required|in:Teaching,Office,Non-Teaching,Guest',
            'payroll_type' => 'required|in:monthly,daily',
            'monthly_salary' => 'nullable|numeric|min:0|required_if:payroll_type,monthly',
            'daily_wage' => 'nullable|numeric|min:0|required_if:payroll_type,daily',
            'salary_expense_head_id' => 'nullable|integer',
            'leave_policy_group' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_ifsc' => 'nullable|string|max:20',
            'max_discount_percent' => 'nullable|integer|min:0|max:100',
            'max_custom_fee_amount' => 'nullable|numeric|min:0',
            'allowed_admission_forms' => 'nullable|in:full,quick,both',
            'restrict_course_access' => 'nullable|boolean',
            'course_permissions' => ['nullable', 'array', function ($attribute, $value, $fail) {
                $courseIds = array_map('intval', array_keys((array) $value));
                $invalidCourseIds = collect($courseIds)->diff(
                    Course::where('institute_id', $this->instituteId())->pluck('id')->map(fn ($id) => (int) $id)
                );

                if ($invalidCourseIds->isNotEmpty()) {
                    $fail('One or more selected courses are invalid.');
                }
            }],
            'restrict_fee_collection_types' => 'nullable|boolean',
            'fee_collection_allowed' => ['nullable', 'array', function ($attribute, $value, $fail) {
                $feeTypeIds = array_map('intval', array_keys((array) $value));
                $invalidFeeTypeIds = collect($feeTypeIds)->diff(
                    FeeType::where('institute_id', $this->instituteId())->pluck('id')->map(fn ($id) => (int) $id)
                );

                if ($invalidFeeTypeIds->isNotEmpty()) {
                    $fail('One or more selected fee collection items are invalid.');
                }
            }],
            'student_visibility_scope' => 'required|in:role_based,self,all',
            'restrict_session_access' => 'nullable|boolean',
            'allowed_session_ids' => 'nullable|array',
            'allowed_session_ids.*' => ['integer', Rule::exists('academic_sessions', 'id')->where('institute_id', $this->instituteId())],
            'payment_modes' => 'nullable|array',
            'payment_modes.*' => 'in:cash,upi,online,cheque,dd,neft,rtgs',
            'payment_bank_ids' => 'nullable|array',
            'payment_bank_ids.*' => ['integer', Rule::exists('institute_bank_accounts', 'id')->where('institute_id', $this->instituteId())],
            'payroll_scope_categories' => 'nullable|array',
            'payroll_scope_categories.*' => 'in:Teaching,Office,Non-Teaching,Guest',
            'fee_discount_allowed' => ['nullable', 'array', function ($attribute, $value, $fail) {
                $feeTypeIds = array_map('intval', array_keys((array) $value));
                $invalidFeeTypeIds = collect($feeTypeIds)->diff(
                    FeeType::where('institute_id', $this->instituteId())->pluck('id')->map(fn ($id) => (int) $id)
                );

                if ($invalidFeeTypeIds->isNotEmpty()) {
                    $fail('One or more selected fee discount items are invalid.');
                }
            }],
            'permission_overrides' => 'nullable|array',
            'permission_overrides.*.effect' => 'nullable|in:allow,deny',
            'permission_overrides.*.expires_at' => 'nullable|date|after_or_equal:today',
            'permission_overrides.*.note' => 'nullable|string|max:255',
        ])->validate();

        try {
            $expenseHeadId = $this->resolveExpenseHeadId(
                $request->input('salary_expense_head_id'),
                $request->input('staff_category')
            );
            $monthlySalary = $request->input('payroll_type') === 'monthly'
                ? (float) ($request->input('monthly_salary') ?? $request->input('salary') ?? 0)
                : null;
            $dailyWage = $request->input('payroll_type') === 'daily'
                ? (float) ($request->input('daily_wage') ?? 0)
                : null;

            $plainPassword = Str::random(8);

            $staffMember = DB::transaction(function () use ($request, $expenseHeadId, $monthlySalary, $dailyWage, $plainPassword) {
                $staffMember = StaffMember::create([
                    'institute_id'  => $this->instituteId(),
                    'staff_uid'     => StaffIdService::generateStaffId($this->instituteId(), (int) date('Y')),
                    'staff_role_id' => $request->staff_role_id,
                    'name'          => strtoupper($request->name),
                    'mobile'        => $request->mobile,
                    'email'         => $request->email,
                    'password'      => Hash::make($plainPassword),
                    'address'       => $request->address,
                    'joining_date'  => $request->joining_date,
                    'salary'        => $monthlySalary,
                    'status'        => true,
                    'staff_category' => $request->staff_category,
                    'payroll_type' => $request->payroll_type,
                    'monthly_salary' => $monthlySalary,
                    'daily_wage' => $dailyWage,
                    'salary_expense_head_id' => $expenseHeadId,
                    'leave_policy_group' => $request->leave_policy_group,
                    'bank_account_number' => $request->bank_account_number,
                    'bank_account_holder' => $request->bank_account_holder,
                    'bank_name' => $request->bank_name,
                    'bank_ifsc' => $request->bank_ifsc,
                    'max_discount_percent' => (int) ($request->input('max_discount_percent') ?? 100),
                    'max_custom_fee_amount' => $request->filled('max_custom_fee_amount') ? (float) $request->input('max_custom_fee_amount') : null,
                    'allowed_admission_forms' => $request->input('allowed_admission_forms', 'both'),
                    'restrict_course_access' => $request->boolean('restrict_course_access'),
                    'restrict_fee_collection_types' => $request->boolean('restrict_fee_collection_types'),
                    'student_visibility_scope' => $request->input('student_visibility_scope', 'role_based'),
                    'restrict_session_access' => $request->boolean('restrict_session_access'),
                    'allowed_session_ids' => array_map('intval', $request->input('allowed_session_ids', [])),
                    'payroll_scope_categories' => $request->input('payroll_scope_categories', []),
                    'can_manage_notices' => $request->boolean('can_manage_notices'),
                ]);

                $this->syncFeeDiscountPermissions($staffMember, $request);
                $this->syncScopes($staffMember, $request);
                $this->syncPermissionOverrides($staffMember, $request);
                $this->syncPaymentModePermissions($staffMember, $request);

                return $staffMember;
            });

            AuditLogService::log($this->instituteId(), 'staff', 'staff_created', 'Staff member created.', $staffMember, [
                'role_id' => $staffMember->staff_role_id,
                'restrict_course_access' => $staffMember->restrict_course_access,
                'restrict_fee_collection_types' => $staffMember->restrict_fee_collection_types,
                'student_visibility_scope' => $staffMember->student_visibility_scope,
                'restrict_session_access' => $staffMember->restrict_session_access,
                'allowed_session_ids' => $staffMember->allowed_session_ids,
                'payroll_scope_categories' => $staffMember->payroll_scope_categories,
                'permission_overrides' => $staffMember->permissionOverrides()
                    ->get(['permission_key', 'effect', 'expires_at'])
                    ->toArray(),
            ]);

            $credentialsSent = $this->sendCredentialsEmail($staffMember, $plainPassword);

            $message = "Staff '{$request->name}' added!";

            if ($credentialsSent) {
                $message .= " Login credentials sent to {$request->email}.";
                return redirect()->route('master.staff-members.index')->with('success', $message);
            }

            $message .= ' Email could not be sent. Copy credentials below.';

            return redirect()->route('master.staff-members.index')
                ->with('success', $message)
                ->with('staff_plain_email', $request->email)
                ->with('staff_plain_password', $plainPassword);
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', $this->formatStaffSaveError($e));
        }
    }

    public function show(StaffMember $staffMember)
    {
        abort_if($staffMember->institute_id !== $this->instituteId(), 403);

        $staffMember->load(['role', 'loans' => function ($query) {
            $query->orderByDesc('id');
        }, 'bonuses' => function ($query) {
            $query->orderByDesc('payment_date');
        }, 'documents' => function ($query) {
            $query->orderByDesc('created_at');
        }]);

        $salaryRecords = $staffMember->salaryRecords()
            ->orderByDesc('salary_year')
            ->orderByDesc('salary_month')
            ->take(6)
            ->get();

        return view('institute.master.staff.members.show', compact('staffMember', 'salaryRecords'));
    }

    public function updateSalaryStructure(Request $request, StaffMember $staffMember)
    {
        abort_if($staffMember->institute_id !== $this->instituteId(), 403);

        $validated = $request->validate([
            'hra_percent'              => 'nullable|integer|min:0|max:100',
            'da_percent'               => 'nullable|integer|min:0|max:100',
            'ta_amount'                => 'nullable|numeric|min:0',
            'medical_amount'           => 'nullable|numeric|min:0',
            'pf_applicable'            => 'nullable|boolean',
            'tds_monthly'              => 'nullable|numeric|min:0',
            'professional_tax_monthly' => 'nullable|numeric|min:0',
        ]);

        $staffMember->update([
            'hra_percent'              => (int) ($validated['hra_percent'] ?? 0),
            'da_percent'               => (int) ($validated['da_percent'] ?? 0),
            'ta_amount'                => (float) ($validated['ta_amount'] ?? 0),
            'medical_amount'           => (float) ($validated['medical_amount'] ?? 0),
            'pf_applicable'            => $request->boolean('pf_applicable'),
            'tds_monthly'              => (float) ($validated['tds_monthly'] ?? 0),
            'professional_tax_monthly' => (float) ($validated['professional_tax_monthly'] ?? 0),
        ]);

        return back()->with('success', 'Salary structure updated. Yeh agli payroll draft generation se apply hogi.');
    }

    public function storeBonus(Request $request, StaffMember $staffMember)
    {
        abort_if($staffMember->institute_id !== $this->instituteId(), 403);

        $validated = $request->validate([
            'bonus_type'   => ['required', Rule::in(array_keys(\App\Models\StaffBonus::$types))],
            'amount'       => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'remarks'      => 'nullable|string|max:300',
        ]);

        $staffMember->bonuses()->create([
            'institute_id'  => $this->instituteId(),
            'bonus_type'    => $validated['bonus_type'],
            'amount'        => $validated['amount'],
            'payment_date'  => $validated['payment_date'],
            'remarks'       => $validated['remarks'] ?? null,
        ]);

        return back()->with('success', 'Bonus record add ho gaya.');
    }

    public function destroyBonus(StaffMember $staffMember, \App\Models\StaffBonus $bonus)
    {
        abort_if($staffMember->institute_id !== $this->instituteId(), 403);
        abort_if($bonus->staff_member_id !== $staffMember->id, 404);

        $bonus->delete();

        return back()->with('success', 'Bonus record delete ho gaya.');
    }

    public function storeDocument(Request $request, StaffMember $staffMember)
    {
        abort_if($staffMember->institute_id !== $this->instituteId(), 403);

        $validated = $request->validate([
            'document_type'   => ['required', Rule::in(array_keys(\App\Models\StaffDocument::$types))],
            'document_number' => 'nullable|string|max:100',
            'issue_date'      => 'nullable|date',
            'expiry_date'     => 'nullable|date',
            'notes'           => 'nullable|string|max:300',
            'file'            => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $filePath = null;
        $originalName = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('staff/documents', 'public');
            $originalName = $request->file('file')->getClientOriginalName();
        }

        $staffMember->documents()->create([
            'institute_id'    => $this->instituteId(),
            'document_type'   => $validated['document_type'],
            'document_number' => $validated['document_number'] ?? null,
            'issue_date'      => $validated['issue_date'] ?? null,
            'expiry_date'     => $validated['expiry_date'] ?? null,
            'file_path'       => $filePath,
            'original_name'   => $originalName,
            'notes'           => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Document upload ho gaya.');
    }

    public function destroyDocument(StaffMember $staffMember, \App\Models\StaffDocument $document)
    {
        abort_if($staffMember->institute_id !== $this->instituteId(), 403);
        abort_if($document->staff_member_id !== $staffMember->id, 404);

        if ($document->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Document delete ho gaya.');
    }

    public function edit(StaffMember $staffMember)
    {
        abort_if($staffMember->institute_id !== $this->instituteId(), 403);
        AccountingSetupService::bootstrapInstitute($this->instituteId());
        $roles = StaffRole::where('institute_id', $this->instituteId())->where('status', true)->get();
        $expenseAccounts = Account::where('institute_id', $this->instituteId())
            ->where('type', 'expense')
            ->where('is_active', true)
            ->whereIn('code', ['3001', '3002'])
            ->orderBy('code')
            ->get();
        $feeTypes = FeeType::where('institute_id', $this->instituteId())
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get();
        $feeDiscountPermissions = $staffMember->feeDiscountPermissions()
            ->pluck('fee_type_id');
        $allowedCourseIds = $this->optionalTableExists('staff_course_permissions')
            ? $staffMember->coursePermissions()->pluck('course_id')->map(fn ($id) => (int) $id)->all()
            : [];
        $feeCollectionPermissions = $this->optionalTableExists('staff_fee_collection_permissions')
            ? $staffMember->feeCollectionPermissions()->pluck('fee_type_id')
            : collect();
        $paymentPermission = PaymentModePermission::where('institute_id', $this->instituteId())
            ->where('user_type', 'staff')
            ->where('user_id', $staffMember->id)
            ->first();
        $courses = Course::where('institute_id', $this->instituteId())
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'course_type_id']);
        $courseTypes = CourseType::forInstitute($this->instituteId())->active()->orderBy('sort_order')->orderBy('name')->get();
        $bankAccounts = InstituteBankAccount::where('institute_id', $this->instituteId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $paymentModes = $this->paymentModeLabels();
        $permissionLabels = StaffRole::permissionLabels();
        $permissionOverrides = $this->optionalTableExists('staff_permission_overrides')
            ? $staffMember->permissionOverrides()->get()->keyBy('permission_key')
            : collect();
        $payrollCategories = ['Teaching', 'Office', 'Non-Teaching', 'Guest'];
        $sessions = AcademicSession::where('institute_id', $this->instituteId())
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();
        $selectedRoleForAccess = $roles->firstWhere('id', (int) old('staff_role_id', $staffMember->staff_role_id));
        $studentVisibilityOptions = $this->studentVisibilityOptions();

        return view('institute.master.staff.members.edit', compact(
            'staffMember', 'roles', 'expenseAccounts', 'feeTypes', 'feeDiscountPermissions',
            'allowedCourseIds', 'feeCollectionPermissions', 'paymentPermission', 'courses', 'courseTypes',
            'bankAccounts', 'paymentModes', 'permissionLabels', 'permissionOverrides', 'payrollCategories',
            'sessions', 'selectedRoleForAccess', 'studentVisibilityOptions'
        ));
    }

    public function update(Request $request, StaffMember $staffMember)
    {
        abort_if($staffMember->institute_id !== $this->instituteId(), 403);

        Validator::make($request->all(), [
            'staff_role_id' => ['required', Rule::exists('staff_roles', 'id')->where('institute_id', $this->instituteId())],
            'name'          => 'required|string|max:100',
            'mobile'        => 'required|digits:10',
            'salary'        => 'nullable|numeric|min:0',
            'staff_category' => 'required|in:Teaching,Office,Non-Teaching,Guest',
            'payroll_type' => 'required|in:monthly,daily',
            'monthly_salary' => 'nullable|numeric|min:0|required_if:payroll_type,monthly',
            'daily_wage' => 'nullable|numeric|min:0|required_if:payroll_type,daily',
            'salary_expense_head_id' => 'nullable|integer',
            'leave_policy_group' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_ifsc' => 'nullable|string|max:20',
            'max_discount_percent' => 'nullable|integer|min:0|max:100',
            'max_custom_fee_amount' => 'nullable|numeric|min:0',
            'allowed_admission_forms' => 'nullable|in:full,quick,both',
            'restrict_course_access' => 'nullable|boolean',
            'course_permissions' => ['nullable', 'array', function ($attribute, $value, $fail) {
                $courseIds = array_map('intval', array_keys((array) $value));
                $invalidCourseIds = collect($courseIds)->diff(
                    Course::where('institute_id', $this->instituteId())->pluck('id')->map(fn ($id) => (int) $id)
                );

                if ($invalidCourseIds->isNotEmpty()) {
                    $fail('One or more selected courses are invalid.');
                }
            }],
            'restrict_fee_collection_types' => 'nullable|boolean',
            'fee_collection_allowed' => ['nullable', 'array', function ($attribute, $value, $fail) {
                $feeTypeIds = array_map('intval', array_keys((array) $value));
                $invalidFeeTypeIds = collect($feeTypeIds)->diff(
                    FeeType::where('institute_id', $this->instituteId())->pluck('id')->map(fn ($id) => (int) $id)
                );

                if ($invalidFeeTypeIds->isNotEmpty()) {
                    $fail('One or more selected fee collection items are invalid.');
                }
            }],
            'student_visibility_scope' => 'required|in:role_based,self,all',
            'restrict_session_access' => 'nullable|boolean',
            'allowed_session_ids' => 'nullable|array',
            'allowed_session_ids.*' => ['integer', Rule::exists('academic_sessions', 'id')->where('institute_id', $this->instituteId())],
            'payment_modes' => 'nullable|array',
            'payment_modes.*' => 'in:cash,upi,online,cheque,dd,neft,rtgs',
            'payment_bank_ids' => 'nullable|array',
            'payment_bank_ids.*' => ['integer', Rule::exists('institute_bank_accounts', 'id')->where('institute_id', $this->instituteId())],
            'payroll_scope_categories' => 'nullable|array',
            'payroll_scope_categories.*' => 'in:Teaching,Office,Non-Teaching,Guest',
            'fee_discount_allowed' => ['nullable', 'array', function ($attribute, $value, $fail) {
                $feeTypeIds = array_map('intval', array_keys((array) $value));
                $invalidFeeTypeIds = collect($feeTypeIds)->diff(
                    FeeType::where('institute_id', $this->instituteId())->pluck('id')->map(fn ($id) => (int) $id)
                );

                if ($invalidFeeTypeIds->isNotEmpty()) {
                    $fail('One or more selected fee discount items are invalid.');
                }
            }],
            'permission_overrides' => 'nullable|array',
            'permission_overrides.*.effect' => 'nullable|in:allow,deny',
            'permission_overrides.*.expires_at' => 'nullable|date|after_or_equal:today',
            'permission_overrides.*.note' => 'nullable|string|max:255',
        ])->validate();

        try {
            $expenseHeadId = $this->resolveExpenseHeadId(
                $request->input('salary_expense_head_id'),
                $request->input('staff_category')
            );
            $monthlySalary = $request->input('payroll_type') === 'monthly'
                ? (float) ($request->input('monthly_salary') ?? $request->input('salary') ?? 0)
                : null;
            $dailyWage = $request->input('payroll_type') === 'daily'
                ? (float) ($request->input('daily_wage') ?? 0)
                : null;

            $newPassword = null;
            DB::transaction(function () use ($request, $staffMember, $expenseHeadId, $monthlySalary, $dailyWage, &$newPassword) {
                $data = [
                    'staff_role_id' => $request->staff_role_id,
                    'name'          => strtoupper($request->name),
                    'mobile'        => $request->mobile,
                    'address'       => $request->address,
                    'joining_date'  => $request->joining_date,
                    'salary'        => $monthlySalary,
                    'staff_category' => $request->staff_category,
                    'payroll_type' => $request->payroll_type,
                    'monthly_salary' => $monthlySalary,
                    'daily_wage' => $dailyWage,
                    'salary_expense_head_id' => $expenseHeadId,
                    'leave_policy_group' => $request->leave_policy_group,
                    'bank_account_number' => $request->bank_account_number,
                    'bank_account_holder' => $request->bank_account_holder,
                    'bank_name' => $request->bank_name,
                    'bank_ifsc' => $request->bank_ifsc,
                    'max_discount_percent' => (int) ($request->input('max_discount_percent') ?? 100),
                    'max_custom_fee_amount' => $request->filled('max_custom_fee_amount') ? (float) $request->input('max_custom_fee_amount') : null,
                    'allowed_admission_forms' => $request->input('allowed_admission_forms', 'both'),
                    'restrict_course_access' => $request->boolean('restrict_course_access'),
                    'restrict_fee_collection_types' => $request->boolean('restrict_fee_collection_types'),
                    'student_visibility_scope' => $request->input('student_visibility_scope', 'role_based'),
                    'restrict_session_access' => $request->boolean('restrict_session_access'),
                    'allowed_session_ids' => array_map('intval', $request->input('allowed_session_ids', [])),
                    'payroll_scope_categories' => $request->input('payroll_scope_categories', []),
                    'can_manage_notices' => $request->boolean('can_manage_notices'),
                ];

                if ($request->boolean('reset_password')) {
                    $newPassword = Str::random(8);
                    $data['password'] = Hash::make($newPassword);
                }

                $staffMember->update($data);
                $this->syncFeeDiscountPermissions($staffMember, $request);
                $this->syncScopes($staffMember, $request);
                $this->syncPermissionOverrides($staffMember, $request);
                $this->syncPaymentModePermissions($staffMember, $request);
            });

            AuditLogService::log($this->instituteId(), 'staff', 'staff_updated', 'Staff member updated.', $staffMember, [
                'role_id' => $staffMember->staff_role_id,
                'restrict_course_access' => $staffMember->restrict_course_access,
                'restrict_fee_collection_types' => $staffMember->restrict_fee_collection_types,
                'student_visibility_scope' => $staffMember->student_visibility_scope,
                'restrict_session_access' => $staffMember->restrict_session_access,
                'allowed_session_ids' => $staffMember->allowed_session_ids,
                'payroll_scope_categories' => $staffMember->payroll_scope_categories,
                'password_reset' => (bool) $newPassword,
                'permission_overrides' => $staffMember->permissionOverrides()
                    ->get(['permission_key', 'effect', 'expires_at'])
                    ->toArray(),
            ]);

            $msg = 'Staff updated!';
            if ($newPassword) {
                $credentialsSent = $this->sendCredentialsEmail($staffMember->fresh(['role', 'institute']), $newPassword);
                $msg .= $credentialsSent
                    ? ' New password sent to the registered email.'
                    : " New Password: {$newPassword}";
            }

            return redirect()->route('master.staff-members.index')->with('success', $msg);
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', $this->formatStaffSaveError($e));
        }
    }

    public function destroy(StaffMember $staffMember)
    {
        abort_if($staffMember->institute_id !== $this->instituteId(), 403);
        try {
            $staffMember->delete();
            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => "Staff \"{$staffMember->name}\" archived successfully."]);
            }
            return redirect()->route('master.staff-members.index')->with('success', "Staff \"{$staffMember->name}\" archived. You can restore from the Archived list.");
        } catch (Throwable $e) {
            $msg = 'Could not archive this staff member. Please try again.';
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }
    }

    public function trashed()
    {
        $staff = StaffMember::onlyTrashed()
            ->where('institute_id', $this->instituteId())
            ->orderByDesc('deleted_at')
            ->get();

        return view('institute.master.staff.members.trashed', compact('staff'));
    }

    public function restore(int $id)
    {
        $member = StaffMember::onlyTrashed()
            ->where('institute_id', $this->instituteId())
            ->findOrFail($id);

        $member->restore();

        return redirect()->route('master.staff-members.trashed')
            ->with('success', "Staff \"{$member->name}\" restored successfully.");
    }

    public function forceDelete(int $id)
    {
        $member = StaffMember::onlyTrashed()
            ->where('institute_id', $this->instituteId())
            ->findOrFail($id);

        $member->forceDelete();

        return redirect()->route('master.staff-members.trashed')
            ->with('success', "Staff \"{$member->name}\" permanently deleted.");
    }

    public function toggle(StaffMember $staffMember)
    {
        abort_if($staffMember->institute_id !== $this->instituteId(), 403);
        $staffMember->update(['status' => !$staffMember->status]);
        return back()->with('success', 'Status updated!');
    }

    public function toggleEmailOtpBypass(StaffMember $staffMember)
    {
        abort_if($staffMember->institute_id !== $this->instituteId(), 403);
        $staffMember->update(['email_otp_bypass' => !$staffMember->email_otp_bypass]);

        AuditLogService::log($this->instituteId(), 'staff', 'staff_email_otp_bypass_toggled',
            'Staff email OTP bypass ' . ($staffMember->email_otp_bypass ? 'enabled' : 'disabled') . '.', $staffMember);

        return back()->with('success', 'Email OTP bypass ' . ($staffMember->email_otp_bypass ? 'enabled' : 'disabled') . '!');
    }

    public function toggleSmsOtpBypass(StaffMember $staffMember)
    {
        abort_if($staffMember->institute_id !== $this->instituteId(), 403);
        $staffMember->update(['sms_otp_bypass' => !$staffMember->sms_otp_bypass]);

        AuditLogService::log($this->instituteId(), 'staff', 'staff_sms_otp_bypass_toggled',
            'Staff SMS OTP bypass ' . ($staffMember->sms_otp_bypass ? 'enabled' : 'disabled') . '.', $staffMember);

        return back()->with('success', 'SMS OTP bypass ' . ($staffMember->sms_otp_bypass ? 'enabled' : 'disabled') . '!');
    }

    public function updateLoginAccess(Request $request, StaffMember $staffMember)
    {
        abort_if($staffMember->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'mode'             => 'required|in:allowed,blocked,suspended',
            'suspended_until'  => 'required_if:mode,suspended|nullable|date|after_or_equal:today',
        ]);

        $staffMember->update([
            'login_blocked'    => $request->mode === 'blocked',
            'suspended_until'  => $request->mode === 'suspended' ? $request->suspended_until : null,
        ]);

        AuditLogService::log($this->instituteId(), 'staff', 'staff_login_access_updated',
            'Staff login access set to ' . $request->mode . '.', $staffMember);

        return back()->with('success', 'Login access updated!');
    }

    private function resolveExpenseHeadId($requestedExpenseHeadId, ?string $staffCategory): ?int
    {
        if ($requestedExpenseHeadId) {
            return (int) Account::where('institute_id', $this->instituteId())
                ->where('type', 'expense')
                ->findOrFail((int) $requestedExpenseHeadId)
                ->id;
        }

        $defaultCode = $staffCategory === 'Teaching' ? '3001' : '3002';

        return Account::where('institute_id', $this->instituteId())
            ->where('code', $defaultCode)
            ->value('id');
    }

    private function syncScopes(StaffMember $staffMember, Request $request): void
    {
        $courseIds  = array_map('intval', array_keys($request->input('course_permissions', [])));
        $feeTypeIds = array_map('intval', array_keys($request->input('fee_collection_allowed', [])));

        if ($this->optionalTableExists('staff_course_permissions')) {
            $staffMember->coursePermissions()->delete();
            foreach ($courseIds as $courseId) {
                StaffCoursePermission::create([
                    'staff_member_id' => $staffMember->id,
                    'course_id' => $courseId,
                ]);
            }
        }

        if ($this->optionalTableExists('staff_fee_collection_permissions')) {
            $staffMember->feeCollectionPermissions()->delete();
            foreach ($feeTypeIds as $feeTypeId) {
                StaffFeeCollectionPermission::create([
                    'staff_member_id' => $staffMember->id,
                    'fee_type_id' => $feeTypeId,
                ]);
            }
        }
    }

    private function syncFeeDiscountPermissions(StaffMember $staffMember, Request $request): void
    {
        $staffMember->feeDiscountPermissions()->delete();

        foreach (array_keys($request->input('fee_discount_allowed', [])) as $feeTypeId) {
            StaffFeeDiscountPermission::create([
                'staff_member_id' => $staffMember->id,
                'fee_type_id' => (int) $feeTypeId,
            ]);
        }
    }

    private function syncPaymentModePermissions(StaffMember $staffMember, Request $request): void
    {
        PaymentModePermission::updateOrCreate(
            [
                'institute_id' => $this->instituteId(),
                'user_type' => 'staff',
                'user_id' => $staffMember->id,
            ],
            [
                'allowed_modes' => array_values($request->input('payment_modes', [])) ?: ['cash'],
                'allowed_bank_ids' => array_map('intval', $request->input('payment_bank_ids', [])),
            ]
        );
    }

    private function syncPermissionOverrides(StaffMember $staffMember, Request $request): void
    {
        if (!$this->optionalTableExists('staff_permission_overrides')) {
            return;
        }

        $staffMember->permissionOverrides()->delete();

        foreach ((array) $request->input('permission_overrides', []) as $permissionKey => $override) {
            $effect = $override['effect'] ?? null;
            if (!in_array($permissionKey, array_keys(StaffRole::permissionLabels()), true) || !$effect) {
                continue;
            }

            StaffPermissionOverride::create([
                'staff_member_id' => $staffMember->id,
                'permission_key' => $permissionKey,
                'effect' => $effect,
                'expires_at' => $override['expires_at'] ?? null,
                'note' => $override['note'] ?? null,
            ]);
        }
    }

    private function formatStaffSaveError(Throwable $e): string
    {
        $message = trim($e->getMessage());

        if ($message === '') {
            return 'Unable to save staff member due to an unexpected error. Please try again or contact support.';
        }

        return "Unable to save staff member. {$message}";
    }
}
