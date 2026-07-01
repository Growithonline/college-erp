<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    public static array $types = [
        'aadhaar'         => 'Aadhaar Card',
        'pan'             => 'PAN Card',
        'driving_license' => 'Driving License',
        'voter_id'        => 'Voter ID',
        'passport'        => 'Passport',
        'certificate'     => 'Certificate',
        'other'           => 'Other',
    ];

    protected $fillable = [
        'institute_id', 'employee_id', 'document_type',
        'document_number', 'issue_date', 'expiry_date',
        'file_path', 'original_name', 'notes',
    ];

    protected $casts = [
        'issue_date'  => 'date',
        'expiry_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return static::$types[$this->document_type] ?? ucfirst($this->document_type);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->expiry_date
            && !$this->is_expired
            && $this->expiry_date->lte(now()->addDays(30));
    }
}
