<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryStaffPermission extends Model
{
    protected $fillable = ['institute_id', 'library_staff_id', 'preset', 'permissions'];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function libraryStaff()
    {
        return $this->belongsTo(LibraryStaff::class);
    }
}
