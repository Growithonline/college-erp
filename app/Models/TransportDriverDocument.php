<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportDriverDocument extends Model
{
    protected $fillable = [
        'institute_id',
        'transport_driver_id',
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
        'DL'                  => 'DL (Driving License)',
        'Aadhaar'             => 'Aadhaar Card',
        'PAN'                 => 'PAN Card',
        'Voter ID'            => 'Voter ID',
        'Medical Certificate' => 'Medical Certificate',
        'Police Verification' => 'Police Verification',
        'Address Proof'       => 'Address Proof',
        'Other'               => 'Other',
    ];

    public function driver()
    {
        return $this->belongsTo(TransportDriver::class, 'transport_driver_id');
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->document_type === 'Other' && $this->document_name) {
            return $this->document_name;
        }
        return static::$types[$this->document_type] ?? $this->document_type;
    }
}
