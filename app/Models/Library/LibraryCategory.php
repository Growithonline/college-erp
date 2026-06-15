<?php

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

class LibraryCategory extends Model
{
    protected $fillable = ['institute_id', 'name', 'code', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeForInstitute($query, int $instituteId)
    {
        return $query->where('institute_id', $instituteId);
    }

    public function books()
    {
        return $this->hasMany(LibraryBook::class, 'category_id');
    }
}
