<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstituteManualIncome extends Model
{
    protected $fillable = [
        'institute_id', 'academic_session_id', 'income_category_id',
        'amount', 'date', 'receipt_no', 'description', 'attachment_path', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date'   => 'date',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function category()
    {
        return $this->belongsTo(InstituteIncomeCategory::class, 'income_category_id');
    }
}
