<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffFeeCollectionPermission extends Model
{
    protected $fillable = ['staff_member_id', 'fee_type_id'];

    public function staff()
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }
}
