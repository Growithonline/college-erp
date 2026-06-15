<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PracticalFeeTokenBatch extends Model
{
    protected $fillable = [
        'institute_id',
        'academic_session_id',
        'course_id',
        'subject_id',
        'course_part_id',
        'year_number',
        'semester',
        'token_amount',
        'payment_mode',
        'collection_date',
        'title',
        'remarks',
        'status',
        'created_by_type',
        'created_by_id',
    ];

    protected $casts = [
        'token_amount' => 'decimal:2',
        'collection_date' => 'date',
    ];

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function coursePart()
    {
        return $this->belongsTo(CoursePart::class);
    }

    public function entries()
    {
        return $this->hasMany(PracticalFeeTokenEntry::class, 'batch_id');
    }

    public function getPostedAmountAttribute(): float
    {
        return (float) $this->entries()->sum('amount');
    }
}
