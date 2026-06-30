<?php
namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class StaffMember extends Authenticatable
{
    use SoftDeletes;
    protected $fillable = [
        'institute_id', 'staff_role_id', 'name', 'mobile', 'email',
        'password', 'photo', 'address', 'joining_date', 'salary', 'status',
        'staff_category', 'payroll_type', 'daily_wage', 'monthly_salary',
        'salary_expense_head_id', 'leave_policy_group', 'bank_account_number',
        'bank_account_holder', 'bank_name', 'bank_ifsc',
        'max_discount_percent', 'max_custom_fee_amount',
        'restrict_course_access', 'restrict_fee_collection_types',
        'allowed_admission_forms',
        'doc_full_form_upload', 'doc_quick_form_upload',
        'student_visibility_scope', 'restrict_session_access', 'allowed_session_ids',
        'payroll_scope_categories',
        'can_manage_notices',
        'hra_percent', 'da_percent', 'ta_amount', 'medical_amount',
        'pf_applicable', 'tds_monthly', 'professional_tax_monthly',
    ];

    protected $hidden  = ['password', 'remember_token'];
    protected $casts   = [
        'joining_date' => 'date',
        'salary' => 'decimal:2',
        'daily_wage' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'status' => 'boolean',
        'max_discount_percent' => 'integer',
        'max_custom_fee_amount' => 'decimal:2',
        'restrict_course_access' => 'boolean',
        'restrict_fee_collection_types' => 'boolean',
        'restrict_session_access' => 'boolean',
        'allowed_session_ids' => 'array',
        'payroll_scope_categories'   => 'array',
        'can_manage_notices'         => 'boolean',
        'hra_percent'                => 'integer',
        'da_percent'                 => 'integer',
        'ta_amount'                  => 'decimal:2',
        'medical_amount'             => 'decimal:2',
        'pf_applicable'              => 'boolean',
        'tds_monthly'                => 'decimal:2',
        'professional_tax_monthly'   => 'decimal:2',
    ];

    public function institute() { return $this->belongsTo(Institute::class); }
    public function role()      { return $this->belongsTo(StaffRole::class, 'staff_role_id'); }
    public function feeDiscountPermissions() { return $this->hasMany(StaffFeeDiscountPermission::class, 'staff_member_id'); }
    public function coursePermissions() { return $this->hasMany(StaffCoursePermission::class, 'staff_member_id'); }
    public function feeCollectionPermissions() { return $this->hasMany(StaffFeeCollectionPermission::class, 'staff_member_id'); }
    public function permissionOverrides() { return $this->hasMany(StaffPermissionOverride::class, 'staff_member_id'); }

    public function libraryMember()
    {
        return $this->hasOne(\App\Models\Library\LibraryMember::class);
    }

    public function attendance()
    {
        return $this->hasMany(StaffAttendance::class, 'staff_member_id');
    }

    public function salaryRecords()
    {
        return $this->hasMany(SalaryRecord::class, 'staff_member_id');
    }

    public function salaryExpenseHead()
    {
        return $this->belongsTo(Account::class, 'salary_expense_head_id');
    }

    public function hasPermission(string $key): bool
    {
        $override = $this->activePermissionOverrides()
            ->where('permission_key', $key)
            ->sortByDesc(fn ($item) => optional($item->expires_at)?->timestamp ?? PHP_INT_MAX)
            ->first();

        if ($override) {
            return $override->effect === 'allow';
        }

        return $this->role?->hasPermission($key) ?? false;
    }

    public function hasAnyPermission(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->hasPermission($key)) {
                return true;
            }
        }

        return false;
    }

    public function canManageAdmissions(): bool
    {
        return $this->hasPermission('admission_add');
    }

    public function canEditAdmissions(): bool
    {
        return $this->hasAnyPermission(['admission_edit', 'student_edit']);
    }

    public function canViewAdmissions(): bool
    {
        return $this->canManageAdmissions()
            || $this->hasPermission('admission_view')
            || $this->hasPermission('student_view')
            || $this->hasPermission('student_promote');
    }

    public function canViewAdmissionReports(): bool
    {
        return $this->hasAnyPermission(['reports', 'admission_reports']);
    }

    public function canCollectFee(): bool
    {
        return $this->hasPermission('fee_collect');
    }

    public function canViewFeeHistory(): bool
    {
        return $this->canCollectFee()
            || $this->hasPermission('fee_view')
            || $this->hasPermission('fee_reports');
    }

    public function canViewFeeWallet(): bool
    {
        return $this->hasAnyPermission([
            'fee_wallet_view',
            'fee_view',
            'fee_reports',
            'fee_collect',
        ]);
    }

    public function canCancelFee(): bool
    {
        return $this->hasPermission('fee_cancel');
    }

    public function canApproveFee(): bool
    {
        return $this->hasPermission('fee_approve');
    }

    public function canManagePracticalTokens(): bool
    {
        return $this->hasPermission('fee_practical_tokens');
    }

    public function canViewFeeReports(): bool
    {
        return $this->hasAnyPermission(['reports', 'fee_reports']);
    }

    public function canViewStatements(): bool
    {
        return $this->hasPermission('get_statement');
    }

    public function canManageStaff(): bool
    {
        return $this->hasPermission('staff_manage');
    }

    public function canApproveAdmissions(): bool
    {
        return $this->hasPermission('admission_approve');
    }

    public function canViewAllAdmissionData(): bool
    {
        if ($this->studentVisibilityScope() === 'all' && $this->canViewAdmissions()) {
            return true;
        }

        if ($this->studentVisibilityScope() === 'self') {
            return false;
        }

        return $this->hasAnyPermission(['reports', 'admission_reports', 'staff_manage']);
    }

    public function canViewAllFeeData(): bool
    {
        return $this->hasAnyPermission(['fee_reports', 'staff_manage']);
    }

    public function hasRestrictedCourseAccess(): bool
    {
        return (bool) $this->restrict_course_access;
    }

    public function hasRestrictedFeeCollectionTypes(): bool
    {
        return (bool) $this->restrict_fee_collection_types;
    }

    public function canUseFullAdmissionForm(): bool
    {
        return in_array($this->allowed_admission_forms ?? 'both', ['full', 'both']);
    }

    public function canUseQuickAdmissionForm(): bool
    {
        return in_array($this->allowed_admission_forms ?? 'both', ['quick', 'both']);
    }

    public function studentVisibilityScope(): string
    {
        $scope = trim((string) ($this->student_visibility_scope ?? 'role_based'));

        return in_array($scope, ['role_based', 'self', 'all'], true) ? $scope : 'role_based';
    }

    public function allowedSessionIds(): array
    {
        return collect($this->allowed_session_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function hasRestrictedSessionAccess(): bool
    {
        return (bool) $this->restrict_session_access;
    }

    public function canAccessAcademicSession(?int $sessionId): bool
    {
        if (!$this->restrict_session_access) {
            return true;
        }

        $allowed = $this->allowedSessionIds();

        // Restriction ON but no sessions selected → deny all (prevents silent bypass)
        if (empty($allowed)) {
            return false;
        }

        if (!$sessionId) {
            return false;
        }

        return in_array($sessionId, $allowed, true);
    }

    public function activePermissionOverrides()
    {
        if (!Schema::hasTable('staff_permission_overrides')) {
            return collect();
        }

        return $this->permissionOverrides
            ->filter(fn (StaffPermissionOverride $override) => $override->isActive())
            ->values();
    }

    public function allowedPayrollCategories(): array
    {
        return collect($this->payroll_scope_categories ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function hasRestrictedPayrollCategories(): bool
    {
        return count($this->allowedPayrollCategories()) > 0;
    }

    public function canAccessPayrollCategory(?string $category): bool
    {
        if (!$this->hasRestrictedPayrollCategories()) {
            return true;
        }

        if (!$category) {
            return false;
        }

        return in_array($category, $this->allowedPayrollCategories(), true);
    }

    public function scopePayrollStaff(Builder $query): Builder
    {
        if ($this->hasRestrictedPayrollCategories()) {
            $query->whereIn('staff_category', $this->allowedPayrollCategories());
        }

        return $query;
    }

    public function allowedCourseIds(): array
    {
        if (!Schema::hasTable('staff_course_permissions')) {
            return [];
        }

        return $this->coursePermissions()
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function allowedFeeCollectionTypeIds(): array
    {
        if (!Schema::hasTable('staff_fee_collection_permissions')) {
            return [];
        }

        return $this->feeCollectionPermissions()
            ->pluck('fee_type_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function canAccessCourse(?int $courseId): bool
    {
        if (!$this->hasRestrictedCourseAccess()) {
            return true;
        }

        $allowed = $this->allowedCourseIds();

        // Restriction ON but no courses selected → deny all (consistent with session behavior)
        if (empty($allowed)) {
            return false;
        }

        if (!$courseId) {
            return false;
        }

        return in_array($courseId, $allowed, true);
    }

    public function canAccessCourseStream(?int $streamId): bool
    {
        if (!$this->hasRestrictedCourseAccess()) {
            return true;
        }

        if (!$streamId) {
            return false;
        }

        return CourseStream::whereKey($streamId)
            ->whereIn('course_id', $this->allowedCourseIds() ?: [-1])
            ->exists();
    }

    public function canAccessStudentForAdmissions(Student $student): bool
    {
        if (!$this->canAccessAcademicSession((int) $student->academic_session_id)) {
            return false;
        }

        if (!$this->canAccessCourseStream((int) $student->course_stream_id)) {
            return false;
        }

        return $this->canViewAllAdmissionData() || (int) $student->admitted_by_staff_id === (int) $this->id;
    }

    public function canAccessStudentForOperations(Student $student): bool
    {
        return $this->canAccessAcademicSession((int) $student->academic_session_id)
            && $this->canAccessCourseStream((int) $student->course_stream_id);
    }

    public function canAccessFeeType(?int $feeTypeId): bool
    {
        if (!$this->hasRestrictedFeeCollectionTypes()) {
            return true;
        }

        if (!$feeTypeId) {
            return false;
        }

        return in_array($feeTypeId, $this->allowedFeeCollectionTypeIds(), true);
    }

    public function canAccessFeeInvoice(FeeInvoice $invoice): bool
    {
        if (!$this->canAccessAcademicSession((int) $invoice->academic_session_id)) {
            return false;
        }

        if ($this->canViewAllFeeData()) {
            return true;
        }

        if ((int) ($invoice->collected_by_staff_id ?? 0) === (int) $this->id) {
            return true;
        }

        return !$invoice->collected_by_staff_id && $invoice->collected_by === $this->name;
    }

    public function scopeAdmissionStudents(Builder $query): Builder
    {
        if ($this->hasRestrictedSessionAccess()) {
            $query->whereIn('academic_session_id', $this->allowedSessionIds());
        }

        if ($this->hasRestrictedCourseAccess()) {
            $query->whereHas('stream', function ($streamQuery) {
                $streamQuery->whereIn('course_id', $this->allowedCourseIds() ?: [-1]);
            });
        }

        if (!$this->canViewAllAdmissionData()) {
            $query->where('admitted_by_staff_id', $this->id);
        }

        return $query;
    }

    public function scopeOperationalStudents(Builder $query): Builder
    {
        if ($this->hasRestrictedSessionAccess()) {
            $query->whereIn('academic_session_id', $this->allowedSessionIds());
        }

        if ($this->hasRestrictedCourseAccess()) {
            $query->whereHas('stream', function ($streamQuery) {
                $streamQuery->whereIn('course_id', $this->allowedCourseIds() ?: [-1]);
            });
        }

        return $query;
    }

    public function scopeFeeInvoices(Builder $query): Builder
    {
        if ($this->hasRestrictedSessionAccess()) {
            $query->whereIn('academic_session_id', $this->allowedSessionIds());
        }

        if ($this->canViewAllFeeData()) {
            return $query;
        }

        return $query->where(function ($invoiceQuery) {
            $invoiceQuery->where('collected_by_staff_id', $this->id)
                ->orWhere(function ($legacyQuery) {
                    $legacyQuery->whereNull('collected_by_staff_id')
                        ->where('collected_by', $this->name);
                });
        });
    }

    public function canViewLibrary(): bool
    {
        return $this->hasPermission('library_view')
            || $this->hasPermission('library_manage')
            || $this->hasPermission('library_issue')
            || $this->hasPermission('library_reports')
            || $this->hasPermission('library_members_manage')
            || $this->hasPermission('library_reservations_manage')
            || $this->hasPermission('library_no_due');
    }

    public function canManageLibrary(): bool
    {
        return $this->hasPermission('library_manage');
    }

    public function canIssueLibraryBooks(): bool
    {
        return $this->hasPermission('library_issue') || $this->canManageLibrary();
    }

    public function canViewLibraryReports(): bool
    {
        return $this->hasPermission('library_reports') || $this->canManageLibrary();
    }

    public function canManageLibraryMembers(): bool
    {
        return $this->hasPermission('library_members_manage') || $this->canManageLibrary();
    }

    public function canManageLibraryReservations(): bool
    {
        return $this->hasPermission('library_reservations_manage') || $this->canManageLibrary();
    }

    public function canGenerateLibraryNoDue(): bool
    {
        return $this->hasPermission('library_no_due') || $this->canManageLibrary();
    }

    // ── Finance helpers ─────────────────────────────────────────────
    public function canViewFinance(): bool
    {
        return $this->hasAnyPermission([
            'finance_view', 'finance_manage', 'expense_create',
            'salary_manage', 'finance_reports', 'ledger_view',
        ]);
    }

    public function canCreateExpense(): bool
    {
        return $this->hasAnyPermission(['expense_create', 'finance_manage']);
    }

    public function canManageSalary(): bool
    {
        return $this->hasPermission('salary_manage');
    }

    public function canViewFinanceReports(): bool
    {
        return $this->hasAnyPermission(['finance_reports', 'ledger_view', 'finance_view']);
    }

    public function canExportReports(): bool
    {
        return $this->hasPermission('report_export');
    }

    // ── Payroll helpers ─────────────────────────────────────────────
    public function canViewPayroll(): bool
    {
        return $this->hasAnyPermission([
            'attendance_view', 'attendance_mark', 'attendance_lock',
            'payroll_generate', 'payroll_approve', 'payroll_pay',
        ]);
    }

    public function canMarkAttendance(): bool
    {
        return $this->hasAnyPermission(['attendance_mark', 'attendance_bulk_mark']);
    }

    public function canViewAttendance(): bool
    {
        return $this->hasAnyPermission(['attendance_view', 'attendance_mark', 'attendance_lock']);
    }

    public function canLockAttendance(): bool
    {
        return $this->hasPermission('attendance_lock');
    }

    public function canGeneratePayroll(): bool
    {
        return $this->hasPermission('payroll_generate');
    }

    public function canApprovePayroll(): bool
    {
        return $this->hasPermission('payroll_approve');
    }

    public function canPaySalary(): bool
    {
        return $this->hasAnyPermission(['payroll_pay', 'salary_manage']);
    }

    public function enabledPermissionLabels(): array
    {
        $labels = [];

        foreach (StaffRole::permissionLabels() as $key => $label) {
            if ($this->hasPermission($key)) {
                $labels[$key] = $label;
            }
        }

        return $labels;
    }
}
