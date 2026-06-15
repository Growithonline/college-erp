<?php

namespace App\Console\Commands;

use App\Models\FeeInvoice;
use App\Models\Institute;
use App\Models\SmsDueReminderSetting;
use App\Models\SmsProviderSetting;
use App\Services\SmsService;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    protected $signature   = 'sms:send-due-reminders {--institute= : Run for specific institute ID only}';
    protected $description = 'Send SMS reminders to students with pending fee payments';

    public function handle(): int
    {
        $onlyInstituteId = $this->option('institute');

        $query = SmsDueReminderSetting::where('is_enabled', true)
            ->with('institute');

        if ($onlyInstituteId) {
            $query->where('institute_id', $onlyInstituteId);
        }

        $settings = $query->get();

        if ($settings->isEmpty()) {
            $this->info('No institutes with due reminders enabled.');
            return 0;
        }

        $totalSent   = 0;
        $totalFailed = 0;

        foreach ($settings as $setting) {
            [$sent, $failed] = $this->processInstitute($setting);
            $totalSent   += $sent;
            $totalFailed += $failed;
            $this->line("Institute #{$setting->institute_id}: {$sent} sent, {$failed} failed");
        }

        $this->info("Done. Total: {$totalSent} sent, {$totalFailed} failed.");
        return 0;
    }

    private function processInstitute(SmsDueReminderSetting $setting): array
    {
        // Bug fix #2: check configured send_time — only run in the correct hour
        $configuredHour = (int) \Carbon\Carbon::parse($setting->send_time)->format('H');
        if ((int) now()->format('H') !== $configuredHour) {
            $this->line("Institute #{$setting->institute_id}: not send_time hour ({$setting->send_time}), skipping.");
            return [0, 0];
        }

        if (! SmsService::isInstituteConfigured($setting->institute_id)) {
            $this->warn("Institute #{$setting->institute_id}: SMS not configured, skipping.");
            return [0, 0];
        }

        $triggerDays = $setting->trigger_days_array;
        $sent = $failed = 0;

        // Bug fix #3: group by student so each student gets ONE SMS with total pending amount
        $allInvoices = FeeInvoice::with('student.course')
            ->where('institute_id', $setting->institute_id)
            ->where('is_cancelled', false)
            ->whereRaw('paid_amount < total_amount')
            ->whereNotNull('payment_date')
            ->get();

        // Group unpaid invoices by student, keep only students where
        // at least one invoice matches a trigger day today
        $byStudent = $allInvoices->groupBy('student_id');

        foreach ($byStudent as $studentId => $invoices) {
            $student = $invoices->first()->student;

            if (! $student || ! $student->mobile) {
                continue;
            }

            // Check if ANY invoice for this student matches a trigger day
            $hasMatch = $invoices->contains(function ($invoice) use ($triggerDays) {
                $daysOverdue = (int) now()->startOfDay()
                    ->diffInDays($invoice->payment_date->startOfDay(), false) * -1;
                return in_array($daysOverdue, $triggerDays, true);
            });

            if (! $hasMatch) {
                continue;
            }

            // Sum ALL pending invoices — one SMS with total amount
            $totalPending  = round($invoices->sum(fn ($i) => $i->total_amount - $i->paid_amount), 2);
            $earliestDue   = $invoices->sortBy('payment_date')->first()->payment_date;

            $message = $this->buildMessage($setting, $student, $totalPending, $earliestDue);

            $result = SmsService::sendForInstitute(
                $setting->institute_id,
                $student->mobile,
                $message,
                'due_reminder'
            );

            $result ? $sent++ : $failed++;
        }

        return [$sent, $failed];
    }

    private function buildMessage(SmsDueReminderSetting $setting, $student, float $amount, $dueDate): string
    {
        $template = $setting->message_template ?? SmsDueReminderSetting::defaultTemplate();

        $vars = [
            '{name}'           => $student->name,
            '{amount}'         => number_format($amount, 0),
            '{due_date}'       => $dueDate->format('d M Y'),
            '{institute_name}' => $setting->institute?->name ?? '',
            '{course}'         => $student->course?->name ?? '',
        ];

        return strtr($template, $vars);
    }
}
