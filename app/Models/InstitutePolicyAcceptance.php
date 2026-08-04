<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutePolicyAcceptance extends Model
{
    protected $fillable = [
        'institute_id',
        'document_type',
        'version',
        'accepted_by',
        'accepted_at',
        'ip_address',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /**
     * Whether the given institute has accepted every document currently
     * configured in config('legal.documents'), at its current version.
     */
    public static function allAccepted(int $instituteId): bool
    {
        $documents = config('legal.documents');

        $accepted = static::where('institute_id', $instituteId)
            ->get(['document_type', 'version'])
            ->mapWithKeys(fn ($row) => [$row->document_type => $row->version]);

        foreach ($documents as $type => $meta) {
            if (($accepted[$type] ?? null) !== $meta['version']) {
                return false;
            }
        }

        return true;
    }
}
