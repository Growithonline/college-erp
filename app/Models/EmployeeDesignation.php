<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDesignation extends Model
{
    protected $fillable = ['institute_id', 'employee_department_id', 'name', 'status', 'transport_role'];

    protected $casts = ['status' => 'boolean'];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function department()
    {
        return $this->belongsTo(EmployeeDepartment::class, 'employee_department_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'employee_designation_id');
    }
}
