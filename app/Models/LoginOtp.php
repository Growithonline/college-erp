<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginOtp extends Model
{
    protected $fillable = [
        'user_id',
        'otp',
        'expires_at',
        'is_used',
    ];
}
