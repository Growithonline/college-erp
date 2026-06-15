<?php

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

class LibraryReservation extends Model
{
    protected $fillable = [
        'institute_id',
        'library_member_id',
        'book_id',
        'fulfilled_copy_id',
        'status',
        'reserved_on',
        'expires_on',
        'remarks',
    ];

    protected $casts = [
        'reserved_on' => 'date',
        'expires_on' => 'date',
    ];

    public function scopeForInstitute($query, int $instituteId)
    {
        return $query->where('institute_id', $instituteId);
    }

    public function member()
    {
        return $this->belongsTo(LibraryMember::class, 'library_member_id');
    }

    public function book()
    {
        return $this->belongsTo(LibraryBook::class, 'book_id');
    }

    public function fulfilledCopy()
    {
        return $this->belongsTo(LibraryBookCopy::class, 'fulfilled_copy_id');
    }
}
