<?php

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

class LibraryRuleSet extends Model
{
    protected $fillable = [
        'institute_id',
        'name',
        'member_type',
        'max_books',
        'loan_days',
        'fine_per_day',
        'grace_days',
        'max_renewals',
        'allow_reservation',
        'is_active',
    ];

    protected $casts = [
        'allow_reservation' => 'boolean',
        'is_active' => 'boolean',
        'fine_per_day' => 'decimal:2',
    ];

    public function scopeForInstitute($query, int $instituteId)
    {
        return $query->where('institute_id', $instituteId);
    }

    public function members()
    {
        return $this->hasMany(LibraryMember::class, 'rule_set_id');
    }
}
