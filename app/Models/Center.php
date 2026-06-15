<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Center extends Authenticatable
{
    use Notifiable;

    private const PERMISSION_MAP = [
        'admission_add'  => 'can_add_admission',
        'student_view'   => 'can_view_students',
        'admission_view' => 'can_view_students',
        'fee_collect'    => 'can_collect_fee',
        'fee_view'       => 'can_collect_fee',
    ];

    protected $fillable = [
        'institute_id', 'name', 'code', 'mobile', 'email',
        'password', 'address', 'city', 'state', 'status',
        // Feature flags
        'can_add_admission', 'can_view_students', 'can_collect_fee',
        // Admission controls
        'admission_form_type', 'allowed_courses', 'allowed_sessions',
        'doc_full_form_upload', 'doc_quick_form_upload',
        // Student scope
        'student_scope',
        // Fee controls
        'fee_scope', 'allowed_pay_modes',
        'can_give_discount', 'max_discount_pct', 'can_waive_fee',
        'restrict_fee_collection_types',
        // Reports
        'can_download_reports',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'can_add_admission'    => 'boolean',
        'can_view_students'    => 'boolean',
        'can_collect_fee'      => 'boolean',
        'status'               => 'boolean',
        'allowed_courses'      => 'array',
        'allowed_sessions'     => 'array',
        'allowed_pay_modes'    => 'array',
        'can_give_discount'              => 'boolean',
        'max_discount_pct'               => 'decimal:2',
        'can_waive_fee'                  => 'boolean',
        'restrict_fee_collection_types'  => 'boolean',
        'can_download_reports'           => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'admission_source_id')
            ->where('admission_source', 'center');
    }

    public function wallet()
    {
        return $this->hasOne(CenterWallet::class);
    }

    public function feeDiscountPermissions()
    {
        return $this->hasMany(CenterFeeDiscountPermission::class);
    }

    public function feeCollectionPermissions()
    {
        return $this->hasMany(CenterFeeCollectionPermission::class);
    }

    public function walletBlockStatus(float $amount = 0): ?array
    {
        $wallet = $this->wallet;
        if (!$wallet) return null;
        return $wallet->getBlockStatus($amount);
    }

    // ── Core permission gate ──────────────────────────────────────────────

    public function hasPermission(string $key): bool
    {
        $col = self::PERMISSION_MAP[$key] ?? $key;
        return (bool) ($this->$col ?? false);
    }

    // ── Feature-level helpers ─────────────────────────────────────────────

    public function canManageAdmissions(): bool { return $this->hasPermission('admission_add'); }
    public function canViewStudents(): bool      { return $this->hasPermission('student_view'); }
    public function canCollectFee(): bool        { return $this->hasPermission('fee_collect'); }
    public function canDownloadReports(): bool   { return (bool) $this->can_download_reports; }

    // ── Admission form type ───────────────────────────────────────────────

    public function canUseFullAdmissionForm(): bool
    {
        return in_array($this->admission_form_type ?? 'both', ['full', 'both']);
    }

    public function canUseQuickAdmissionForm(): bool
    {
        return in_array($this->admission_form_type ?? 'both', ['quick', 'both']);
    }

    // ── Course & session restrictions ─────────────────────────────────────

    /** null = all courses allowed */
    public function isAllowedCourse(int $courseId): bool
    {
        return $this->allowed_courses === null || in_array($courseId, $this->allowed_courses);
    }

    /**
     * Parse allowed_sessions into a normalized list.
     * Handles both old format [3,5,6] and new format [{"id":3,"admission":true,"fee":false}].
     * Returns null when unrestricted.
     */
    private function sessionPermissionList(): ?array
    {
        $raw = $this->allowed_sessions;
        if ($raw === null) return null;
        if (empty($raw)) return [];

        // Old format: plain integer IDs → all permissions default true, scope defaults own
        if (is_int($raw[0] ?? null)) {
            return array_map(fn($id) => [
                'id'            => (int) $id,
                'admission'     => true,
                'fee'           => true,
                'view'          => true,
                'student_scope' => 'own',
                'fee_scope'     => 'own',
            ], $raw);
        }

        return $raw;
    }

    /** null = all sessions allowed for admission */
    public function canAdmitInSession(int $sessionId): bool
    {
        $list = $this->sessionPermissionList();
        if ($list === null) return true;
        foreach ($list as $s) {
            if ((int) ($s['id'] ?? 0) === $sessionId) return (bool) ($s['admission'] ?? true);
        }
        return false;
    }

    /** null = all sessions allowed for fee collection */
    public function canCollectFeeInSession(int $sessionId): bool
    {
        $list = $this->sessionPermissionList();
        if ($list === null) return true;
        foreach ($list as $s) {
            if ((int) ($s['id'] ?? 0) === $sessionId) return (bool) ($s['fee'] ?? true);
        }
        return false;
    }

    /** null = all sessions allowed for student view */
    public function canViewStudentsInSession(int $sessionId): bool
    {
        $list = $this->sessionPermissionList();
        if ($list === null) return true;
        foreach ($list as $s) {
            if ((int) ($s['id'] ?? 0) === $sessionId) return (bool) ($s['view'] ?? true);
        }
        return false;
    }

    /** Returns 'own' or 'all' for student visibility in a specific session. Falls back to global student_scope. */
    public function studentScopeForSession(int $sessionId): string
    {
        $list = $this->sessionPermissionList();
        if ($list !== null) {
            foreach ($list as $s) {
                if ((int) ($s['id'] ?? 0) === $sessionId) {
                    return $s['student_scope'] ?? ($this->student_scope ?? 'own');
                }
            }
        }
        return $this->student_scope ?? 'own';
    }

    /** Returns 'own' or 'all' for fee collection scope in a specific session. Falls back to global fee_scope. */
    public function feeScopeForSession(int $sessionId): string
    {
        $list = $this->sessionPermissionList();
        if ($list !== null) {
            foreach ($list as $s) {
                if ((int) ($s['id'] ?? 0) === $sessionId) {
                    return $s['fee_scope'] ?? ($this->fee_scope ?? 'own');
                }
            }
        }
        return $this->fee_scope ?? 'own';
    }

    /** Helper for blade: returns map of session_id => [admission,fee,view,student_scope,fee_scope] or null if unrestricted */
    public function sessionPermsMap(): ?array
    {
        $list = $this->sessionPermissionList();
        if ($list === null) return null;
        $map = [];
        foreach ($list as $s) {
            $map[(int) ($s['id'] ?? 0)] = [
                'admission'     => (bool) ($s['admission'] ?? true),
                'fee'           => (bool) ($s['fee'] ?? true),
                'view'          => (bool) ($s['view'] ?? true),
                'student_scope' => $s['student_scope'] ?? 'own',
                'fee_scope'     => $s['fee_scope'] ?? 'own',
            ];
        }
        return $map;
    }

    // ── Student & fee scope ───────────────────────────────────────────────

    public function isStudentScopeOwn(): bool
    {
        return ($this->student_scope ?? 'own') === 'own';
    }

    public function isFeesScopeOwn(): bool
    {
        return ($this->fee_scope ?? 'own') === 'own';
    }

    // ── Payment & discount controls ───────────────────────────────────────

    /** null = all modes allowed */
    public function isAllowedPayMode(string $mode): bool
    {
        return $this->allowed_pay_modes === null || in_array($mode, $this->allowed_pay_modes);
    }

    public function getMaxDiscountPercent(): float
    {
        return $this->can_give_discount ? (float) $this->max_discount_pct : 0.0;
    }

    public function hasRestrictedFeeCollectionTypes(): bool
    {
        return (bool) $this->restrict_fee_collection_types;
    }

    public function allowedFeeCollectionTypeIds(): array
    {
        return $this->feeCollectionPermissions()->pluck('fee_type_id')->map(fn ($id) => (int) $id)->all();
    }

    public function allowedFeeDiscountTypeIds(): array
    {
        return $this->feeDiscountPermissions()->pluck('fee_type_id')->map(fn ($id) => (int) $id)->all();
    }

    // ── Guard name ────────────────────────────────────────────────────────

    public function getGuardName(): string
    {
        return 'center';
    }
}
