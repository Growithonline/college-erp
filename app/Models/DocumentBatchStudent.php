<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentBatchStudent extends Model
{
    protected $fillable = [
        'document_batch_id', 'student_id', 'found_at', 'distributed_at',
        'received_by_name', 'fee_amount', 'income_category_id',
        'manual_income_id', 'remarks',
    ];

    protected $casts = [
        'found_at'       => 'datetime',
        'distributed_at' => 'datetime',
        'fee_amount'     => 'decimal:2',
    ];

    public function batch()
    {
        return $this->belongsTo(DocumentBatch::class, 'document_batch_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function incomeCategory()
    {
        return $this->belongsTo(InstituteIncomeCategory::class, 'income_category_id');
    }

    public function manualIncome()
    {
        return $this->belongsTo(InstituteManualIncome::class, 'manual_income_id');
    }

    public function getIsFoundAttribute(): bool
    {
        return $this->found_at !== null;
    }

    public function getIsDistributedAttribute(): bool
    {
        return $this->distributed_at !== null;
    }
}
