<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportHelper extends Model
{
    protected $fillable = ['institute_id', 'employee_id', 'name', 'mobile', 'status', 'notes'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    protected $casts = ['status' => 'boolean'];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function routeAssignments()
    {
        return $this->hasMany(TransportRouteAssignment::class);
    }
}
