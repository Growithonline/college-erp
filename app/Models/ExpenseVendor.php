<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseVendor extends Model
{
    protected $table = 'expense_vendors';

    protected $fillable = [
        'institute_id', 'l2_id', 'name',
        'gst_no', 'pan_no', 'contact_name', 'contact_phone', 'contact_email',
        'address', 'notes', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(ExpenseCategoryL2::class, 'l2_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
