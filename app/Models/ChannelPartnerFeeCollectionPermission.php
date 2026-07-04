<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelPartnerFeeCollectionPermission extends Model
{
    protected $fillable = ['channel_partner_id', 'fee_type_id'];

    public function channelPartner() { return $this->belongsTo(ChannelPartner::class); }
    public function feeType()        { return $this->belongsTo(FeeType::class); }
}
