<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class ChannelPartner extends Authenticatable
{
    private const PERMISSION_MAP = [
        'admission_add'  => 'can_add_admission',
        'student_view'   => 'can_view_students',
        'admission_view' => 'can_view_students',
        'fee_collect'    => 'can_collect_fee',
        'fee_view'       => 'can_collect_fee',
    ];

    protected $fillable = [
        'institute_id', 'name', 'mobile', 'email', 'password',
        'address', 'city', 'state', 'commission_percent', 'status',
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
        // Reports
        'can_download_reports',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'can_add_admission'    => 'boolean',
        'can_view_students'    => 'boolean',
        'can_collect_fee'      => 'boolean',
        'status'               => 'boolean',
        'commission_percent'   => 'decimal:2',
        'allowed_courses'      => 'array',
        'allowed_sessions'     => 'array',
        'allowed_pay_modes'    => 'array',
        'can_give_discount'    => 'boolean',
        'max_discount_pct'     => 'decimal:2',
        'can_waive_fee'        => 'boolean',
        'can_download_reports' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function wallet()
    {
        return $this->hasOne(ChannelWallet::class, 'channel_partner_id');
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
        return in_array($this->admission_form_type ?? 'quick', ['full', 'both']);
    }

    public function canUseQuickAdmissionForm(): bool
    {
        return in_array($this->admission_form_type ?? 'quick', ['quick', 'both']);
    }

    // ── Course & session restrictions ─────────────────────────────────────

    public function isAllowedCourse(int $courseId): bool
    {
        return $this->allowed_courses === null || in_array($courseId, $this->allowed_courses);
    }

    private function sessionPermissionList(): ?array
    {
        $raw = $this->allowed_sessions;
        if ($raw === null) return null;
        if (empty($raw)) return [];

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

    public function canAdmitInSession(int $sessionId): bool
    {
        $list = $this->sessionPermissionList();
        if ($list === null) return true;
        foreach ($list as $s) {
            if ((int) ($s['id'] ?? 0) === $sessionId) return (bool) ($s['admission'] ?? true);
        }
        return false;
    }

    public function canCollectFeeInSession(int $sessionId): bool
    {
        $list = $this->sessionPermissionList();
        if ($list === null) return true;
        foreach ($list as $s) {
            if ((int) ($s['id'] ?? 0) === $sessionId) return (bool) ($s['fee'] ?? true);
        }
        return false;
    }

    public function canViewStudentsInSession(int $sessionId): bool
    {
        $list = $this->sessionPermissionList();
        if ($list === null) return true;
        foreach ($list as $s) {
            if ((int) ($s['id'] ?? 0) === $sessionId) return (bool) ($s['view'] ?? true);
        }
        return false;
    }

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

    public function isAllowedPayMode(string $mode): bool
    {
        return $this->allowed_pay_modes === null || in_array($mode, $this->allowed_pay_modes);
    }

    public function getMaxDiscountPercent(): float
    {
        return $this->can_give_discount ? (float) $this->max_discount_pct : 0.0;
    }
}
