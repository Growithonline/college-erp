<?php

namespace App\Models\Library;

use App\Models\StaffMember;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;

class LibraryMember extends Model
{
    protected $fillable = [
        'institute_id',
        'member_type',
        'student_id',
        'staff_member_id',
        'rule_set_id',
        'member_code',
        'name',
        'mobile',
        'email',
        'status',
        'joined_on',
        'blocked_reason',
    ];

    protected $casts = [
        'joined_on' => 'date',
    ];

    public function scopeForInstitute($query, int $instituteId)
    {
        return $query->where('institute_id', $instituteId);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function ruleSet()
    {
        return $this->belongsTo(LibraryRuleSet::class, 'rule_set_id');
    }

    public function transactions()
    {
        return $this->hasMany(LibraryTransaction::class, 'library_member_id');
    }

    public function finePayments()
    {
        return $this->hasMany(LibraryFinePayment::class, 'library_member_id');
    }

    public function reservations()
    {
        return $this->hasMany(LibraryReservation::class, 'library_member_id');
    }

    public function activeTransactions()
    {
        return $this->hasMany(LibraryTransaction::class, 'library_member_id')->where('current_status', 'issued');
    }

    public function getPendingFineAttribute(): float
    {
        return (float) $this->transactions()
            ->selectRaw('COALESCE(SUM(fine_amount - fine_paid), 0) as amount')
            ->value('amount');
    }
}
