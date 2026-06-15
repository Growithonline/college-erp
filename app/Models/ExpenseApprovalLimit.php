<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseApprovalLimit extends Model
{
    protected $table = 'expense_approval_limits';

    protected $fillable = ['institute_id', 'staff_role_id', 'max_auto_approve_amount'];

    protected $casts = ['max_auto_approve_amount' => 'decimal:2'];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function role()
    {
        return $this->belongsTo(StaffRole::class, 'staff_role_id');
    }
}
