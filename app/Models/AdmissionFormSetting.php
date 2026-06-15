<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionFormSetting extends Model
{
    protected $fillable = [
        'institute_id',
        'form_type',
        'field_config',
        'form_config',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }
}
