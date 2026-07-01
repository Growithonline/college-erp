<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryDisbursement extends Model
{
    protected $fillable = [
        'institute_id', 'employee_id', 'month', 'year',
        'basic_paid', 'total_allowances', 'gross_salary', 'deductions', 'net_salary',
        'payment_date', 'payment_mode', 'status', 'remarks',
    ];

    protected $casts = [
        'basic_paid'       => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'gross_salary'     => 'decimal:2',
        'deductions'       => 'decimal:2',
        'net_salary'       => 'decimal:2',
        'payment_date'     => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }
}
