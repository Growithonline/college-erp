<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinanceSetting;
use Illuminate\Support\Facades\DB;

class AccountingSetupService
{
    public static function bootstrapInstitute(int $instituteId): array
    {
        return DB::transaction(function () use ($instituteId) {
            $created = 0;
            $updated = 0;
            $accountsByCode = [];

            foreach (self::defaultAccountDefinitions() as $definition) {
                $parentId = null;
                if (!empty($definition['parent_code'])) {
                    $parentId = $accountsByCode[$definition['parent_code']]->id ?? null;
                }

                $account = Account::firstOrNew([
                    'institute_id' => $instituteId,
                    'code' => $definition['code'],
                ]);

                $wasExisting = $account->exists;

                $account->fill([
                    'parent_id' => $parentId,
                    'name' => $definition['name'],
                    'type' => $definition['type'],
                    'normal_side' => $definition['normal_side'],
                    'is_system' => true,
                    'is_active' => true,
                    'allow_manual_posting' => $definition['allow_manual_posting'] ?? true,
                    'meta' => $definition['meta'] ?? null,
                ]);
                $account->save();

                $accountsByCode[$definition['code']] = $account;
                if ($wasExisting) {
                    $updated++;
                } else {
                    $created++;
                }
            }

            $settings = FinanceSetting::firstOrCreate(
                ['institute_id' => $instituteId],
                [
                    'fees_receivable_account_id' => $accountsByCode['1003']->id ?? null,
                    'student_advance_account_id' => $accountsByCode['4000']->id ?? null,
                    'discount_allowed_account_id' => $accountsByCode['2301']->id ?? null,
                    'cash_account_id' => $accountsByCode['1000']->id ?? null,
                    'fine_income_account_id' => $accountsByCode['2005']->id ?? null,
                    'rounding_adjustment_account_id' => $accountsByCode['2300']->id ?? null,
                ]
            );

            return [
                'created' => $created,
                'updated' => $updated,
                'settings_id' => $settings->id,
            ];
        });
    }

    private static function defaultAccountDefinitions(): array
    {
        return [
            ['code' => '1000', 'name' => 'Cash In Hand', 'type' => 'asset', 'normal_side' => 'debit'],
            ['code' => '1001', 'name' => 'Bank Accounts', 'type' => 'asset', 'normal_side' => 'debit', 'allow_manual_posting' => false],
            ['code' => '1002', 'name' => 'Student Wallet Control', 'type' => 'asset', 'normal_side' => 'debit'],
            ['code' => '1003', 'name' => 'Fees Receivable', 'type' => 'asset', 'normal_side' => 'debit'],
            ['code' => '2000', 'name' => 'Student Fee Income', 'type' => 'income', 'normal_side' => 'credit', 'allow_manual_posting' => false],
            ['code' => '2001', 'name' => 'Registration Fee Income', 'type' => 'income', 'normal_side' => 'credit', 'parent_code' => '2000'],
            ['code' => '2002', 'name' => 'Course Fee Income', 'type' => 'income', 'normal_side' => 'credit', 'parent_code' => '2000'],
            ['code' => '2003', 'name' => 'Subject Fee Income', 'type' => 'income', 'normal_side' => 'credit', 'parent_code' => '2000'],
            ['code' => '2004', 'name' => 'Exam Fee Income', 'type' => 'income', 'normal_side' => 'credit', 'parent_code' => '2000'],
            ['code' => '2005', 'name' => 'Fine Income', 'type' => 'income', 'normal_side' => 'credit', 'parent_code' => '2000'],
            ['code' => '2100', 'name' => 'Scholarship Received', 'type' => 'income', 'normal_side' => 'credit'],
            ['code' => '2200', 'name' => 'CSR And Donation', 'type' => 'income', 'normal_side' => 'credit'],
            ['code' => '2300', 'name' => 'Other Income', 'type' => 'income', 'normal_side' => 'credit'],
            ['code' => '2301', 'name' => 'Discount Allowed', 'type' => 'expense', 'normal_side' => 'debit'],
            ['code' => '3000', 'name' => 'Staff Expenses', 'type' => 'expense', 'normal_side' => 'debit'],
            ['code' => '3001', 'name' => 'Teaching Staff Salary', 'type' => 'expense', 'normal_side' => 'debit', 'parent_code' => '3000'],
            ['code' => '3002', 'name' => 'Non-Teaching Staff Salary', 'type' => 'expense', 'normal_side' => 'debit', 'parent_code' => '3000'],
            ['code' => '3003', 'name' => 'PF Employer Contribution', 'type' => 'expense', 'normal_side' => 'debit', 'parent_code' => '3000'],
            ['code' => '3004', 'name' => 'ESI Employer Contribution', 'type' => 'expense', 'normal_side' => 'debit', 'parent_code' => '3000'],
            ['code' => '3100', 'name' => 'Infrastructure Expenses', 'type' => 'expense', 'normal_side' => 'debit'],
            ['code' => '3101', 'name' => 'Electricity Expense', 'type' => 'expense', 'normal_side' => 'debit', 'parent_code' => '3100'],
            ['code' => '3102', 'name' => 'Rent Expense', 'type' => 'expense', 'normal_side' => 'debit', 'parent_code' => '3100'],
            ['code' => '3103', 'name' => 'Maintenance Expense', 'type' => 'expense', 'normal_side' => 'debit', 'parent_code' => '3100'],
            ['code' => '3200', 'name' => 'Academic Expenses', 'type' => 'expense', 'normal_side' => 'debit'],
            ['code' => '3201', 'name' => 'Stationery Expense', 'type' => 'expense', 'normal_side' => 'debit', 'parent_code' => '3200'],
            ['code' => '3202', 'name' => 'Lab Equipment Expense', 'type' => 'expense', 'normal_side' => 'debit', 'parent_code' => '3200'],
            ['code' => '3203', 'name' => 'Library Books Expense', 'type' => 'expense', 'normal_side' => 'debit', 'parent_code' => '3200'],
            ['code' => '3300', 'name' => 'Transport Expense', 'type' => 'expense', 'normal_side' => 'debit'],
            ['code' => '3400', 'name' => 'Miscellaneous Expense', 'type' => 'expense', 'normal_side' => 'debit'],
            ['code' => '1004', 'name' => 'Staff Advance Receivable', 'type' => 'asset', 'normal_side' => 'debit'],
            ['code' => '4000', 'name' => 'Student Advance', 'type' => 'liability', 'normal_side' => 'credit'],
            ['code' => '4001', 'name' => 'TDS Payable', 'type' => 'liability', 'normal_side' => 'credit'],
            ['code' => '4002', 'name' => 'PF Payable', 'type' => 'liability', 'normal_side' => 'credit'],
            ['code' => '4003', 'name' => 'ESI Payable', 'type' => 'liability', 'normal_side' => 'credit'],
            ['code' => '4004', 'name' => 'Professional Tax Payable', 'type' => 'liability', 'normal_side' => 'credit'],
        ];
    }
}
