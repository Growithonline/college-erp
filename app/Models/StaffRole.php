<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StaffRole extends Model
{
    public const PERMISSION_GROUPS = [
        'Admissions' => [
            'admission_add' => 'Add Admission',
            'admission_edit' => 'Edit Admission',
            'admission_view' => 'View Admissions',
            'admission_approve' => 'Approve Admission',
            'admission_delete' => 'Delete Admission',
            'admission_reports' => 'Admission Reports',
        ],
        'Students' => [
            'student_view' => 'View Students',
            'student_edit' => 'Edit Students',
            'student_promote' => 'Promote Students',
            'reports' => 'View General Reports',
            'report_export' => 'Export Reports',
        ],
        'Fee' => [
            'fee_collect' => 'Collect Fee',
            'fee_view' => 'View Fee',
            'fee_cancel' => 'Cancel Fee Receipt',
            'fee_approve' => 'Approve Pending Fee Collections',
            'fee_wallet_view' => 'View Fee Wallet',
            'fee_practical_tokens' => 'Manage Practical Fee Tokens',
            'fee_reports' => 'Fee Reports',
        ],
        'Statements' => [
            'get_statement' => 'Get Statement',
            'statement_export' => 'Export Statement',
        ],
        'Library' => [
            'library_view' => 'View Library',
            'library_manage' => 'Manage Library Masters',
            'library_issue' => 'Issue / Return Library Books',
            'library_reports' => 'View Library Reports',
            'library_members_manage' => 'Manage Library Members',
            'library_reservations_manage' => 'Manage Reservations',
            'library_no_due' => 'Generate No Dues',
        ],
        'Finance' => [
            'finance_view' => 'View Finance Reports',
            'finance_manage' => 'Manage Finance Entries',
            'expense_create' => 'Create Expenses',
            'expense_approve' => 'Approve Expenses',
            'salary_manage' => 'Manage Salary Book',
            'finance_reports' => 'Advanced Finance Reports',
            'ledger_view' => 'View Ledger / Books',
        ],
        'Payroll' => [
            'attendance_mark' => 'Mark Daily Attendance',
            'attendance_bulk_mark' => 'Bulk Mark Attendance',
            'attendance_lock' => 'Lock / Unlock Attendance',
            'attendance_view' => 'View Attendance',
            'payroll_generate' => 'Generate Payroll Draft',
            'payroll_approve' => 'Approve Payroll',
            'payroll_pay' => 'Mark Payroll Paid',
        ],
        'Administration' => [
            'staff_manage' => 'Manage Staff',
            'notice_post' => 'Post Notice',
        ],
        'Documents' => [
            'document_view'   => 'View Admission Documents',
            'document_upload' => 'Upload Admission Documents',
            'document_edit'   => 'Replace/Edit Documents',
            'document_verify' => 'Verify / Approve Documents',
            'document_delete' => 'Delete Documents',
        ],
    ];

    protected $fillable = ['institute_id', 'name', 'is_system', 'permissions', 'status'];

    protected $casts = [
        'permissions' => 'array',
        'is_system'   => 'boolean',
        'status'      => 'boolean',
    ];

    public function institute()    { return $this->belongsTo(Institute::class); }
    public function staffMembers() { return $this->hasMany(StaffMember::class); }

    public function getPermissionsAttribute($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decodedValue = $value;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            if (is_array($decodedValue)) {
                return $decodedValue;
            }

            if (!is_string($decodedValue) || trim($decodedValue) === '') {
                break;
            }

            $decodedValue = json_decode($decodedValue, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                break;
            }
        }

        return [];
    }

    public function hasPermission(string $key): bool
    {
        return (bool) ($this->permissions[$key] ?? false);
    }

    public static function permissionLabels(): array
    {
        return collect(self::PERMISSION_GROUPS)->collapse()->all();
    }

    public static function permissionGroups(): array
    {
        return self::PERMISSION_GROUPS;
    }
}
