<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategoryL2 extends Model
{
    protected $table = 'expense_categories_l2';

    protected $fillable = ['institute_id', 'l1_id', 'name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategoryL1::class, 'l1_id');
    }

    public function vendors()
    {
        return $this->hasMany(ExpenseVendor::class, 'l2_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
