<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportParticular extends Model
{
    protected $fillable = [
        'institute_id',
        'section',
        'source_type',
        'fee_type_id',
        'item_type',
        'course_id',
        'year_number',
        'income_category_id',
        'expense_category_l1_id',
        'salary_scope',
        'name',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'year_number' => 'integer',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function incomeCategory()
    {
        return $this->belongsTo(InstituteIncomeCategory::class, 'income_category_id');
    }

    public function expenseCategoryL1()
    {
        return $this->belongsTo(ExpenseCategoryL1::class, 'expense_category_l1_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
