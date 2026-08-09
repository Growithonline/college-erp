<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'name',
        'status',
        'institute_quota',
        'per_institute_student_limit',
        'institute_subscription_type',
        'institute_subscription_end',
    ];

    protected $casts = [
        'status' => 'boolean',
        'institute_subscription_end' => 'date',
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
