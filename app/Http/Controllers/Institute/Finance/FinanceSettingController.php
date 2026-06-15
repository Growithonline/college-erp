<?php

namespace App\Http\Controllers\Institute\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\FeeType;
use App\Models\FinanceSetting;
use App\Models\InstituteBankAccount;
use App\Services\AccountingSetupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FinanceSettingController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    private function ensureFinanceTablesReady(): ?RedirectResponse
    {
        foreach (['accounts', 'finance_settings'] as $table) {
            if (!Schema::hasTable($table)) {
                return redirect()
                    ->route('institute.dashboard')
                    ->with('error', 'Finance settings abhi ready nahi hain. Pehle finance migrations run karo.');
            }
        }

        return null;
    }

    public function index(): View|RedirectResponse
    {
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();
        AccountingSetupService::bootstrapInstitute($instituteId);

        $leafAccounts = Account::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->get();

        $accounts       = $leafAccounts;
        $incomeAccounts = $leafAccounts->where('type', 'income')->values();
        $assetAccounts  = $leafAccounts->where('type', 'asset')->values();

        $settings = FinanceSetting::firstOrCreate(['institute_id' => $instituteId]);

        $feeTypes = FeeType::where('institute_id', $instituteId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $bankAccounts = InstituteBankAccount::where('institute_id', $instituteId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $mappedFeeTypes = $feeTypes->whereNotNull('income_account_id')->count();
        $mappedBankAccounts = $bankAccounts->whereNotNull('gl_account_id')->count();

        return view('institute.finance.settings.index', compact(
            'accounts',
            'incomeAccounts',
            'assetAccounts',
            'settings',
            'feeTypes',
            'bankAccounts',
            'mappedFeeTypes',
            'mappedBankAccounts'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();
        AccountingSetupService::bootstrapInstitute($instituteId);

        $validated = $request->validate([
            'fees_receivable_account_id' => 'nullable|integer',
            'student_advance_account_id' => 'nullable|integer',
            'discount_allowed_account_id' => 'nullable|integer',
            'cash_account_id' => 'nullable|integer',
            'fine_income_account_id' => 'nullable|integer',
            'rounding_adjustment_account_id' => 'nullable|integer',
            'fee_type_accounts' => 'nullable|array',
            'fee_type_accounts.*' => 'nullable|integer',
            'bank_account_accounts' => 'nullable|array',
            'bank_account_accounts.*' => 'nullable|integer',
        ]);

        $allowedAccountIds = Account::where('institute_id', $instituteId)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $allowedAccountIds = array_flip($allowedAccountIds);

        $normalizeAccountId = function ($value) use ($allowedAccountIds): ?int {
            if ($value === null || $value === '' || (int) $value <= 0) {
                return null;
            }

            $accountId = (int) $value;
            if (!isset($allowedAccountIds[$accountId])) {
                abort(422, 'Selected account does not belong to this institute.');
            }

            return $accountId;
        };

        $settings = FinanceSetting::updateOrCreate(
            ['institute_id' => $instituteId],
            [
                'fees_receivable_account_id' => $normalizeAccountId($validated['fees_receivable_account_id'] ?? null),
                'student_advance_account_id' => $normalizeAccountId($validated['student_advance_account_id'] ?? null),
                'discount_allowed_account_id' => $normalizeAccountId($validated['discount_allowed_account_id'] ?? null),
                'cash_account_id' => $normalizeAccountId($validated['cash_account_id'] ?? null),
                'fine_income_account_id' => $normalizeAccountId($validated['fine_income_account_id'] ?? null),
                'rounding_adjustment_account_id' => $normalizeAccountId($validated['rounding_adjustment_account_id'] ?? null),
            ]
        );

        $feeTypeMap = $validated['fee_type_accounts'] ?? [];
        FeeType::where('institute_id', $instituteId)
            ->get()
            ->each(function (FeeType $feeType) use ($feeTypeMap, $normalizeAccountId) {
                $feeType->update([
                    'income_account_id' => $normalizeAccountId($feeTypeMap[$feeType->id] ?? null),
                ]);
            });

        $bankMap = $validated['bank_account_accounts'] ?? [];
        InstituteBankAccount::where('institute_id', $instituteId)
            ->get()
            ->each(function (InstituteBankAccount $bankAccount) use ($bankMap, $normalizeAccountId) {
                $bankAccount->update([
                    'gl_account_id' => $normalizeAccountId($bankMap[$bankAccount->id] ?? null),
                ]);
            });

        return redirect()
            ->route('finance.settings.index')
            ->with('success', 'Finance mappings update ho gayi hain.');
    }
}
