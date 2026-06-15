<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportVehicleType extends Model
{
    protected $fillable = ['institute_id', 'name', 'default_capacity', 'status'];

    protected $casts = [
        'default_capacity' => 'integer',
        'status'           => 'boolean',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function vehicles()
    {
        return $this->hasMany(TransportVehicle::class);
    }
}
