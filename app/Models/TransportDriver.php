<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportDriver extends Model
{
    protected $fillable = [
        'institute_id',
        'employee_id',
        'name',
        'mobile',
        'license_no',
        'license_expiry',
        'status',
        'notes',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    protected $casts = [
        'status' => 'boolean',
        'license_expiry' => 'date',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function allocations()
    {
        return $this->hasMany(TransportAllocation::class);
    }

    public function documents()
    {
        return $this->hasMany(TransportDriverDocument::class);
    }
}
