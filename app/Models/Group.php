<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function institutes()
    {
        return $this->hasMany(Institute::class);
    }

    public function groupAdmins()
    {
        return $this->hasMany(GroupAdmin::class);
    }
}
