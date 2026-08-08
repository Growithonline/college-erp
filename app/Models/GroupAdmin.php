<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class GroupAdmin extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'group_id',
        'name',
        'email',
        'password',
        'can_reset_institute_password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'can_reset_institute_password' => 'boolean',
        'status' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
