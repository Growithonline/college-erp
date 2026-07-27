<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseDocumentFee extends Model
{
    protected $fillable = [
        'institute_id', 'course_id', 'marksheet_fee', 'degree_fee',
    ];

    protected $casts = [
        'marksheet_fee' => 'decimal:2',
        'degree_fee'    => 'decimal:2',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function feeFor(string $documentType): ?string
    {
        return $documentType === 'degree' ? $this->degree_fee : $this->marksheet_fee;
    }
}
