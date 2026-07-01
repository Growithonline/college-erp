<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDepartment extends Model
{
    protected $fillable = ['institute_id', 'name', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function designations()
    {
        return $this->hasMany(EmployeeDesignation::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
