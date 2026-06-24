<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    protected $fillable = [
        'institute_id',
        'name',
        'academic_year',
        'start_date',
        'end_date',
        'is_active',
        'current_semester',
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

    /**
     * Institute admin ke liye "view session" return karo.
     * Agar admin ne sidebar se koi session select kiya hai (PHP session mein stored)
     * to woh return hoti hai — warna DB ki is_active=true session.
     * Staff/center/channel ke liye yeh method call nahi hoti — unka data change nahi hota.
     */
    public static function viewSession(int $instituteId): ?self
    {
        $viewId = session('institute_view_session_id');
        if ($viewId) {
            $sess = static::where('institute_id', $instituteId)->find($viewId);
            if ($sess) return $sess;
        }
        return static::where('institute_id', $instituteId)->where('is_active', true)->first();
    }

    public static function viewSessionId(int $instituteId): ?int
    {
        return static::viewSession($instituteId)?->id;
    }

    // Kya admin abhi ek non-active session view kar raha hai?
    public static function isViewingPastSession(int $instituteId): bool
    {
        $viewId = session('institute_view_session_id');
        if (!$viewId) return false;
        return !static::where('institute_id', $instituteId)
            ->where('id', $viewId)
            ->where('is_active', true)
            ->exists();
    }
}