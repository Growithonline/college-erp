<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnquiryFollowUp extends Model
{
    protected $fillable = [
        'enquiry_id', 'staff_id', 'type', 'note', 'next_follow_up_at', 'status',
    ];

    protected $casts = [
        'next_follow_up_at' => 'datetime',
    ];

    public function enquiry() { return $this->belongsTo(Enquiry::class); }
    public function staff()   { return $this->belongsTo(StaffMember::class, 'staff_id'); }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->next_follow_up_at && $this->next_follow_up_at->isPast();
    }
}
