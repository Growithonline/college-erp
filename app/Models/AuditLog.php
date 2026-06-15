<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'institute_id',
        'actor_type',
        'actor_id',
        'module',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
