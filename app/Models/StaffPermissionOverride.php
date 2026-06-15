<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffPermissionOverride extends Model
{
    protected $fillable = [
        'staff_member_id',
        'permission_key',
        'effect',
        'expires_at',
        'note',
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function isActive(): bool
    {
        return !$this->expires_at || $this->expires_at->isToday() || $this->expires_at->isFuture();
    }
}
