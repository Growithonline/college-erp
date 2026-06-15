<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryLoginLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['library_staff_id', 'ip_address', 'user_agent', 'status'];

    public function libraryStaff()
    {
        return $this->belongsTo(LibraryStaff::class);
    }
}
