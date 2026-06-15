<?php

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

class LibraryAuthor extends Model
{
    protected $fillable = ['institute_id', 'name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeForInstitute($query, int $instituteId)
    {
        return $query->where('institute_id', $instituteId);
    }

    public function books()
    {
        return $this->belongsToMany(LibraryBook::class, 'library_book_author', 'author_id', 'book_id')->withTimestamps();
    }
}
