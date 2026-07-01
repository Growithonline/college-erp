<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'institute_id', 'employee_department_id', 'employee_designation_id',
        'employee_code', 'name', 'father_name', 'dob', 'gender', 'blood_group',
        'phone', 'alternate_phone', 'email', 'address', 'city', 'state', 'pincode', 'photo',
        'joining_date', 'employment_type', 'salary_type', 'basic_salary', 'status', 'notes',
    ];

    protected $casts = [
        'dob'          => 'date',
        'joining_date' => 'date',
        'basic_salary' => 'decimal:2',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function department()
    {
        return $this->belongsTo(EmployeeDepartment::class, 'employee_department_id');
    }

    public function designation()
    {
        return $this->belongsTo(EmployeeDesignation::class, 'employee_designation_id');
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function salaryComponents()
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    public function currentSalaryComponents()
    {
        return $this->hasMany(EmployeeSalaryComponent::class)->whereNull('effective_to');
    }

    public function disbursements()
    {
        return $this->hasMany(EmployeeSalaryDisbursement::class);
    }

    public function bonuses()
    {
        return $this->hasMany(EmployeeBonus::class);
    }

    public function advances()
    {
        return $this->hasMany(EmployeeAdvance::class);
    }

    public function activeAdvances()
    {
        return $this->hasMany(EmployeeAdvance::class)->where('status', 'active');
    }

    // Documents with expiry coming in next N days
    public function expiringDocuments(int $days = 30)
    {
        return $this->documents()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days))
            ->whereDate('expiry_date', '>=', now());
    }
}
