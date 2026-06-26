<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class LibraryStaff extends Authenticatable
{
    use HasFactory;

    protected $table = 'library_staff';

    protected $fillable = [
        'institute_id', 'employee_id', 'name', 'email', 'password', 'phone',
        'photo', 'gender', 'date_of_birth', 'address',
        'designation', 'joining_date', 'shift', 'assigned_section',
        'qualification', 'status', 'staff_member_id',
        'login_attempts', 'locked_until', 'last_login_at', 'last_login_ip',
    ];

    protected $casts = [
        'date_of_birth'  => 'date',
        'joining_date'   => 'date',
        'status'         => 'boolean',
        'locked_until'   => 'datetime',
        'last_login_at'  => 'datetime',
    ];

    protected $hidden = ['password', 'remember_token'];

    const DESIGNATION_LABELS = [
        'librarian'            => 'Librarian',
        'assistant_librarian'  => 'Assistant Librarian',
        'attendant'            => 'Library Attendant',
        'data_entry'           => 'Data Entry Operator',
    ];

    const SHIFT_LABELS = [
        'morning' => 'Morning',
        'evening' => 'Evening',
        'both'    => 'Both',
    ];

    const PERMISSION_GROUPS = [
        'Catalog' => [
            'books_view'          => 'View Books',
            'books_create'        => 'Add Books',
            'books_edit'          => 'Edit Books',
            'books_delete'        => 'Delete Books',
            'categories_manage'   => 'Manage Categories',
            'authors_manage'      => 'Manage Authors',
            'publishers_manage'   => 'Manage Publishers',
            'racks_manage'        => 'Manage Racks',
            'vendors_manage'      => 'Manage Vendors',
        ],
        'Circulation' => [
            'issue_create'    => 'Issue Books',
            'return_process'  => 'Process Returns',
            'renew_process'   => 'Process Renewals',
            'fine_view'       => 'View Fines',
            'fine_collect'    => 'Collect Fines',
        ],
        'Members' => [
            'members_view' => 'View Members',
        ],
        'Reports' => [
            'reports_view'   => 'View Reports',
            'reports_export' => 'Export Reports',
        ],
    ];

    const PRESETS = [
        'full_librarian' => [
            'books_view', 'books_create', 'books_edit', 'books_delete',
            'categories_manage', 'authors_manage', 'publishers_manage',
            'racks_manage', 'vendors_manage',
            'issue_create', 'return_process', 'renew_process',
            'fine_view', 'fine_collect', 'members_view',
            'reports_view', 'reports_export',
        ],
        'attendant' => [
            'books_view', 'issue_create', 'return_process',
            'renew_process', 'fine_view', 'fine_collect', 'members_view',
        ],
        'data_entry' => [
            'books_view', 'books_create', 'books_edit',
            'categories_manage', 'authors_manage', 'publishers_manage',
            'racks_manage', 'vendors_manage',
        ],
        'read_only' => [
            'books_view', 'members_view', 'reports_view',
        ],
    ];

    const PRESET_LABELS = [
        'full_librarian' => 'Full Librarian',
        'attendant'      => 'Library Attendant',
        'data_entry'     => 'Data Entry Operator',
        'read_only'      => 'Read Only',
        'custom'         => 'Custom',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function permissionRecord()
    {
        return $this->hasOne(LibraryStaffPermission::class);
    }

    public function loginLogs()
    {
        return $this->hasMany(LibraryLoginLog::class);
    }

    // ── Permission helpers ───────────────────────────────────────────

    public function hasPermission(string $key): bool
    {
        $perms = $this->permissionRecord?->permissions ?? [];
        return in_array($key, $perms);
    }

    public function getPermissions(): array
    {
        return $this->permissionRecord?->permissions ?? [];
    }

    // ── Library operation proxies (used by BaseLibraryController + views) ──

    public function canViewLibrary(): bool              { return $this->hasPermission('books_view'); }
    public function canManageLibrary(): bool            { return $this->hasPermission('books_edit'); }
    public function canIssueLibraryBooks(): bool        { return $this->hasPermission('issue_create'); }
    public function canViewLibraryReports(): bool       { return $this->hasPermission('reports_view'); }
    public function canManageLibraryMembers(): bool     { return $this->hasPermission('members_view'); }
    public function canManageLibraryReservations(): bool{ return $this->hasPermission('issue_create'); }
    public function canGenerateLibraryNoDue(): bool     { return $this->hasPermission('members_view'); }
    public function canCollectLibraryFines(): bool      { return $this->hasPermission('fine_collect'); }

    // ── Security helpers ────────────────────────────────────────────

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function isDualRole(): bool
    {
        return $this->staff_member_id !== null;
    }

    public function getDesignationLabelAttribute(): string
    {
        return self::DESIGNATION_LABELS[$this->designation] ?? $this->designation;
    }

    // ── Employee ID generation ───────────────────────────────────────

    public static function generateEmployeeId(int $instituteId): string
    {
        $year = date('Y');
        $last = static::where('institute_id', $instituteId)
            ->where('employee_id', 'like', "LIB-{$year}-%")
            ->orderByDesc('id')
            ->value('employee_id');

        $num = $last ? ((int) substr($last, -4)) + 1 : 1;

        return 'LIB-' . $year . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
