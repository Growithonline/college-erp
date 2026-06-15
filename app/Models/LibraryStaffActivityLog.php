<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryStaffActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'library_staff_id', 'action', 'subject', 'details', 'ip_address',
    ];

    // Readable action labels
    const ACTION_LABELS = [
        'login'          => 'Login',
        'logout'         => 'Logout',
        'profile_update' => 'Profile Updated',
        'ip_change'      => 'IP Address Changed',
        'otp_sent'       => 'OTP Requested',
        'session_kicked' => 'Session Terminated (New Login)',
    ];

    public function libraryStaff()
    {
        return $this->belongsTo(LibraryStaff::class);
    }

    public static function record(
        int $staffId,
        string $action,
        ?string $subject = null,
        ?string $details = null,
        ?string $ip = null
    ): void {
        static::create([
            'library_staff_id' => $staffId,
            'action'           => $action,
            'subject'          => $subject,
            'details'          => $details,
            'ip_address'       => $ip,
        ]);
    }
}
