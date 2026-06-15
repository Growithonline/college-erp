<?php

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

class LibraryBookCopy extends Model
{
    protected $fillable = [
        'institute_id',
        'book_id',
        'rack_id',
        'vendor_id',
        'accession_no',
        'barcode',
        'purchase_date',
        'price',
        'status',
        'condition_note',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'price' => 'decimal:2',
    ];

    public function scopeForInstitute($query, int $instituteId)
    {
        return $query->where('institute_id', $instituteId);
    }

    public function book()
    {
        return $this->belongsTo(LibraryBook::class, 'book_id');
    }

    public function rack()
    {
        return $this->belongsTo(LibraryRack::class, 'rack_id');
    }

    public function vendor()
    {
        return $this->belongsTo(LibraryVendor::class, 'vendor_id');
    }

    public function transactions()
    {
        return $this->hasMany(LibraryTransaction::class, 'library_book_copy_id');
    }
}
