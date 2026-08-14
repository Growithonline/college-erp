<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthInstitute;
use Illuminate\Database\Eloquent\Model;

class ExpenseVendor extends Model
{
    use BelongsToAuthInstitute;

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

    public function workOrders()
    {
        return $this->hasMany(ExpenseVendorWorkOrder::class, 'expense_vendor_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
