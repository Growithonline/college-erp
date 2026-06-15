<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'institute_id',
        'name',
        'code',
        'credit',
        'has_practical',
        'status',
    ];

    protected $casts = [
        'has_practical' => 'boolean',
        'status'        => 'boolean',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function components()
    {
        return $this->hasMany(SubjectComponent::class);
    }

    public function theoryComponent()
    {
        return $this->hasOne(SubjectComponent::class)->where('component_type', 'theory');
    }

    public function practicalComponent()
    {
        return $this->hasOne(SubjectComponent::class)->where('component_type', 'practical');
    }
}
