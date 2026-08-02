<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthInstitute;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategoryL1 extends Model
{
    use BelongsToAuthInstitute;

    protected $table = 'expense_categories_l1';

    protected $fillable = ['institute_id', 'name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function subCategories()
    {
        return $this->hasMany(ExpenseCategoryL2::class, 'l1_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
