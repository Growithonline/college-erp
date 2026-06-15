<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstituteIncomeCategory extends Model
{
    protected $fillable = ['institute_id', 'name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function manualIncomes()
    {
        return $this->hasMany(InstituteManualIncome::class, 'income_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
