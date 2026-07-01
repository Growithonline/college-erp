<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportHelper extends Model
{
    protected $fillable = ['institute_id', 'name', 'mobile', 'status', 'notes'];

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
