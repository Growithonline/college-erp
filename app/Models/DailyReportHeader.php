<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyReportHeader extends Model
{
    protected $fillable = [
        'institute_id',
        'report_date',
        'book_no',
        'rec_range_from',
        'rec_range_to',
        'online_range_from',
        'online_range_to',
        'sr_no',
        'activities',
        'created_by',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
