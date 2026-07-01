<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAdvance extends Model
{
    protected $fillable = [
        'institute_id', 'employee_id', 'amount', 'given_date',
        'recovery_per_month', 'recovered_amount', 'status', 'remarks',
    ];

    protected $casts = [
        'amount'              => 'decimal:2',
        'recovery_per_month'  => 'decimal:2',
        'recovered_amount'    => 'decimal:2',
        'given_date'          => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getPendingAmountAttribute(): float
    {
        return max(0, (float)$this->amount - (float)$this->recovered_amount);
    }
}
