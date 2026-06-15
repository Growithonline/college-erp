<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsDueReminderSetting extends Model
{
    protected $fillable = [
        'institute_id',
        'is_enabled',
        'trigger_days',
        'message_template',
        'send_time',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public static function defaultTemplate(): string
    {
        return 'Dear {name}, your fee of Rs.{amount} is pending. Please pay at the earliest. -{institute_name}';
    }

    public function getTriggerDaysArrayAttribute(): array
    {
        return array_map('intval', explode(',', $this->trigger_days ?? '0,3,7'));
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }
}
