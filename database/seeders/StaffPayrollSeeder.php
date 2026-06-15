<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Institute;
use App\Models\StaffMember;

class StaffPayrollSeeder extends Seeder
{
    /**
     * Seed staff payroll setup for an institute
     */
    public static function setupPayroll(Institute $institute): void
    {
        // Note: This seeder adds sample data. Customize based on your needs.
        
        // Update existing staff with payroll information (optional)
        // This is just a template - adjust based on your staff structure
        
        // Ensure necessary account heads exist
        \App\Services\AccountingSetupService::ensurePayrollAccounts($institute->id);
    }

    public function run(): void
    {
        // Run for all institutes
        foreach (Institute::all() as $institute) {
            self::setupPayroll($institute);
        }
    }
}
