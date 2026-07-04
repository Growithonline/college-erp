<?php

use App\Console\Commands\SendDueReminders;
use App\Models\Institute;
use App\Services\AccountingSetupService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('finance:bootstrap {institute_id?}', function (?int $instituteId = null) {
    $institutes = $instituteId
        ? Institute::whereKey($instituteId)->get()
        : Institute::orderBy('id')->get();

    if ($institutes->isEmpty()) {
        $this->warn('No institute found for finance bootstrap.');
        return;
    }

    foreach ($institutes as $institute) {
        $result = AccountingSetupService::bootstrapInstitute((int) $institute->id);
        $this->info(sprintf(
            'Institute #%d %s: accounts created=%d, updated=%d, finance_settings=%d',
            $institute->id,
            $institute->name,
            $result['created'],
            $result['updated'],
            $result['settings_id']
        ));
    }
})->purpose('Bootstrap accounting chart and finance settings for one or all institutes');

// Send due payment reminder SMS — runs every hour, command itself checks per-institute send_time
Schedule::command('sms:send-due-reminders')->hourly()->withoutOverlapping();

// Full database backup — daily at 2:00 AM server time, keep last 14 days
Schedule::command('db:backup --keep=14')->dailyAt('02:00')->withoutOverlapping();
