<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Services\AccountingSetupService;
use App\Models\Institute;

class SetupPayrollModule extends Command
{
    protected $signature = 'payroll:setup {--institute-id= : Specific institute ID (optional)}';
    protected $description = 'Setup staff attendance and payroll module';

    public function handle(): int
    {
        $this->info('🚀 Setting up Staff Attendance & Payroll Module...');

        try {
            // Step 1: Run migrations
            $this->info('📦 Running migrations...');
            Artisan::call('migrate', ['--force' => true]);
            $this->info('✅ Migrations completed');

            // Step 2: Setup accounting accounts if needed
            $this->info('💰 Setting up GL accounts...');
            
            if ($instituteId = $this->option('institute-id')) {
                $institute = Institute::findOrFail($instituteId);
                AccountingSetupService::bootstrapInstitute($institute->id);
                $this->info("✅ Payroll accounts setup for Institute: {$institute->name}");
            } else {
                foreach (Institute::all() as $institute) {
                    AccountingSetupService::bootstrapInstitute($institute->id);
                }
                $this->info('✅ Payroll accounts setup for all institutes');
            }

            // Step 3: Summary
            $this->newLine();
            $this->info('✨ Payroll module setup completed successfully!');
            $this->newLine();
            $this->info('📋 Next steps:');
            $this->line('1. Update staff members with:');
            $this->line('   - staff_category (Teaching, Office, Non-Teaching, Guest)');
            $this->line('   - payroll_type (monthly or daily)');
            $this->line('   - monthly_salary or daily_wage');
            $this->line('   - salary_expense_head_id');
            $this->newLine();
            $this->line('2. Access the module at:');
            $this->line('   - Daily Attendance: /payroll/attendance/daily');
            $this->line('   - Monthly Summary: /payroll/attendance/monthly');
            $this->line('   - Generate Salary: /payroll/generate-draft');
            $this->line('   - Payroll Summary: /payroll/summary');
            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
