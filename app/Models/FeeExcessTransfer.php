<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeExcessTransfer extends Model
{
    public const ACTIVE   = 'active';
    public const REVERTED = 'reverted';

    protected $fillable = [
        'institute_id', 'student_id', 'academic_session_id', 'from_label', 'to_label',
        'amount', 'status', 'created_by_type', 'created_by_id', 'note', 'reverted_at',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'reverted_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
