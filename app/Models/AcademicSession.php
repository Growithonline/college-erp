<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    protected $fillable = [
        'institute_id',
        'name',
        'start_date',
        'end_date',
        'is_active',
        'current_semester',   // Bug #5 Fix: fillable mein add kiya
    ];

    protected $casts = [
        'start_date'       => 'date',
        'end_date'         => 'date',
        'is_active'        => 'boolean',
        'current_semester' => 'integer',  // Bug #5 Fix: cast add kiya
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function feeAssignments()
    {
        return $this->hasMany(FeeAssignment::class);
    }

    // Sirf ek session active hogi — yeh scope use karo
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Activate karo — baaki sab deactivate ho jayengi
    public function activate(): void
    {
        static::where('institute_id', $this->institute_id)
              ->where('id', '!=', $this->id)
              ->update(['is_active' => false]);

        $this->update(['is_active' => true]);
    }

    // Helper: current semester safely return karo (never null)
    public function getCurrentSemesterAttribute($value): int
    {
        return $value ?? 1;
    }
}