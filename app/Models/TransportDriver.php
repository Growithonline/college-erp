<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportDriver extends Model
{
    protected $fillable = [
        'institute_id',
        'name',
        'mobile',
        'license_no',
        'license_expiry',
        'helper_name',
        'helper_mobile',
        'status',
        'notes',
    ];

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
