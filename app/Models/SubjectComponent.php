<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectComponent extends Model
{
    protected $fillable = [
        'subject_id',
        'component_type',   // theory / practical
        'max_marks',
        'pass_marks',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function feeAssignments()
    {
        return $this->hasMany(FeeAssignment::class);
    }

    public function isTheory(): bool
    {
        return $this->component_type === 'theory';
    }

    public function isPractical(): bool
    {
        return $this->component_type === 'practical';
    }
}
