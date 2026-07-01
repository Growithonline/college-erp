<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryComponent extends Model
{
    public static array $types = [
        'hra'        => 'HRA',
        'conveyance' => 'Conveyance',
        'medical'    => 'Medical',
        'special'    => 'Special Allowance',
        'other'      => 'Other',
    ];

    protected $fillable = [
        'employee_id', 'component_type', 'label', 'amount', 'effective_from', 'effective_to',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'effective_from' => 'date',
        'effective_to'   => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: (static::$types[$this->component_type] ?? $this->component_type);
    }
}
