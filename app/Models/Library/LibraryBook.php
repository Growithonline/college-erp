<?php

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

class LibraryBook extends Model
{
    protected $fillable = [
        'institute_id',
        'category_id',
        'publisher_id',
        'subject_id',
        'title',
        'subtitle',
        'isbn',
        'edition',
        'language',
        'subject_name',
        'author_text',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeForInstitute($query, int $instituteId)
    {
        return $query->where('institute_id', $instituteId);
    }

    public function category()
    {
        return $this->belongsTo(LibraryCategory::class, 'category_id');
    }

    public function publisher()
    {
        return $this->belongsTo(LibraryPublisher::class, 'publisher_id');
    }

    public function subject()
    {
        return $this->belongsTo(LibrarySubject::class, 'subject_id');
    }

    public function authors()
    {
        return $this->belongsToMany(LibraryAuthor::class, 'library_book_author', 'book_id', 'author_id')->withTimestamps();
    }

    public function copies()
    {
        return $this->hasMany(LibraryBookCopy::class, 'book_id');
    }

    public function reservations()
    {
        return $this->hasMany(LibraryReservation::class, 'book_id');
    }

    public function getAvailableCopiesCountAttribute(): int
    {
        return $this->copies->where('status', 'available')->count();
    }
}
