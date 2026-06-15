<?php

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

class LibraryRack extends Model
{
    protected $fillable = ['institute_id', 'room_name', 'rack_code', 'shelf_code', 'remarks', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeForInstitute($query, int $instituteId)
    {
        return $query->where('institute_id', $instituteId);
    }

    public function copies()
    {
        return $this->hasMany(LibraryBookCopy::class, 'rack_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $name = $this->rack_code;

        if ($this->shelf_code) {
            $name .= ' / ' . $this->shelf_code;
        }

        if ($this->room_name) {
            $name .= ' (' . $this->room_name . ')';
        }

        return $name;
    }
}
