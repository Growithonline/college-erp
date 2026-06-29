<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportVehicleDocument extends Model
{
    protected $fillable = [
        'institute_id',
        'transport_vehicle_id',
        'document_type',
        'document_name',
        'file_path',
        'original_name',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public static array $types = [
        'RC'             => 'RC (Registration Certificate)',
        'PUC'            => 'PUC (Pollution Under Control)',
        'Permit'         => 'Permit',
        'Insurance'      => 'Insurance',
        'Fitness'        => 'Fitness Certificate',
        'Road Tax'       => 'Road Tax',
        'National Permit'=> 'National Permit',
        'Goods Permit'   => 'Goods Permit',
        'Speed Limiter'  => 'Speed Limiter Certificate',
        'Other'          => 'Other',
    ];

    public function vehicle()
    {
        return $this->belongsTo(TransportVehicle::class, 'transport_vehicle_id');
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->document_type === 'Other' && $this->document_name) {
            return $this->document_name;
        }
        return static::$types[$this->document_type] ?? $this->document_type;
    }
}
