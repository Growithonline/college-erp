<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CenterFeeCollectionPermission extends Model
{
    protected $fillable = ['center_id', 'fee_type_id'];

    public function center()  { return $this->belongsTo(Center::class); }
    public function feeType() { return $this->belongsTo(FeeType::class); }
}
