<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletExtensionRequest extends Model
{
    protected $fillable = [
        'entity_type', 'entity_id', 'institute_id',
        'request_type', 'reason',
        'requested_days', 'requested_amount',
        'status', 'admin_note', 'processed_by', 'processed_at',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'processed_at'     => 'datetime',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function entity()
    {
        if ($this->entity_type === 'center') {
            return $this->belongsTo(Center::class, 'entity_id');
        }
        return $this->belongsTo(ChannelPartner::class, 'entity_id');
    }

    public function getEntityNameAttribute(): string
    {
        if ($this->entity_type === 'center') {
            return Center::find($this->entity_id)?->name ?? '—';
        }
        return ChannelPartner::find($this->entity_id)?->name ?? '—';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
