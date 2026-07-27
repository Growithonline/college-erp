<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffBonus extends Model
{
    public static array $types = [
        'diwali'  => 'Diwali Bonus',
        'holi'    => 'Holi Bonus',
        'eid'     => 'Eid Bonus',
        'annual'  => 'Annual Bonus',
        'adhoc'   => 'Ad-hoc Bonus',
    ];

    protected $fillable = [
        'institute_id', 'staff_member_id', 'bonus_type', 'amount',
        'payment_date', 'payment_mode', 'remarks',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class);
    }
}
