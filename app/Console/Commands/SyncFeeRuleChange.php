<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\FeeRuleSyncService;
use Illuminate\Console\Command;

// Command-line front end of FeeRuleSyncService (see it for exactly what is compared and posted).
// Dry-run by default; --apply writes. The same service backs the admin preview/apply page on
// the Course Fee Structure screen.
class SyncFeeRuleChange extends Command
{
    protected $signature = 'fees:sync-rule-change
        {--institute= : Institute id}
        {--session= : Live academic session id}
        {--course= : Limit to one course id}
        {--fee-type= : Limit to one fee_type_id}
        {--student= : Limit to one student id (pilot run)}
        {--from= : Only adjust rows whose ledger-charged amount equals this (the old rule amount)}
        {--include-new : Also charge items that were never charged to the student (rule added later)}
        {--include-removed : Also reverse items that no longer have a rule}
        {--apply : Actually write the changes (default is dry-run)}
        {--yes : Skip the confirmation prompt when --apply is used}';

    protected $description = 'Sync live-session students to the current course fee rules (ledger, wallet, journal, carried-forward snapshot). Dry-run unless --apply.';

    public function handle(): int
    {
        $instituteId = (int) $this->option('institute');
        $sessionId   = (int) $this->option('session');

        if ($instituteId <= 0 || $sessionId <= 0) {
            $this->error('Required: --institute and --session.');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');

        if ($apply && !$this->option('yes')) {
            $total = Student::where('institute_id', $instituteId)->where('academic_session_id', $sessionId)->where('status', 'active')->count();
            if (!$this->confirm("Apply fee-rule sync to up to {$total} student(s)? Take a DB backup first.")) {
                return self::FAILURE;
            }
        }

        $report = FeeRuleSyncService::run($instituteId, $sessionId, [
            'course'          => $this->option('course') ? (int) $this->option('course') : null,
            'fee_type'        => $this->option('fee-type') ? (int) $this->option('fee-type') : null,
            'student'         => $this->option('student') ? (int) $this->option('student') : null,
            'from'            => $this->option('from') !== null ? (float) $this->option('from') : null,
            'include_new'     => (bool) $this->option('include-new'),
            'include_removed' => (bool) $this->option('include-removed'),
        ], $apply);

        $mode = $apply ? 'APPLIED' : 'DRY-RUN';

        if ($report['checked'] === 0) {
            $this->warn('No matching students.');
            return self::SUCCESS;
        }

        if (empty($report['rows'])) {
            $this->info("{$mode}: {$report['checked']} student(s) checked, nothing to change.");
            return self::SUCCESS;
        }

        $this->table(
            ['id', 'student_uid', 'fee', 'result', 'diff', 'note'],
            array_map(fn($r) => [
                $r['student_id'], $r['uid'], $r['fee'], $r['result'],
                $r['diff'] != 0.0 ? number_format($r['diff'], 2, '.', '') : '', $r['note'],
            ], $report['rows'])
        );

        $this->info("{$mode}: {$report['checked']} student(s) checked, {$report['changed']} adjustment(s) (+"
            . number_format($report['increase'], 2) . ' / -' . number_format($report['decrease'], 2)
            . "), {$report['skipped']} skipped/failed.");

        return self::SUCCESS;
    }
}
