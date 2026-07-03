<?php

namespace App\Http\Controllers\Institute\Employee;

use App\Models\Account;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeBonus;
use App\Models\EmployeeSalaryComponent;
use App\Models\EmployeeSalaryDisbursement;
use App\Models\FinanceSetting;
use App\Models\InstituteBankAccount;
use App\Services\AccountingSetupService;
use App\Services\InstituteWalletService;
use App\Services\JournalService;
use Illuminate\Http\Request;

class EmployeeSalaryController extends EmployeeBaseController
{
    // ── Salary Components ──────────────────────────────────────────────────

    public function storeComponent(Request $request, Employee $employee)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);

        $data = $request->validate([
            'component_type' => ['required', 'in:hra,conveyance,medical,special,other'],
            'label'          => ['nullable', 'string', 'max:80'],
            'amount'         => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
        ]);

        // Close previous component of same type
        EmployeeSalaryComponent::where('employee_id', $employee->id)
            ->where('component_type', $data['component_type'])
            ->whereNull('effective_to')
            ->update(['effective_to' => date('Y-m-d', strtotime($data['effective_from'] . ' -1 day'))]);

        EmployeeSalaryComponent::create([
            'employee_id'    => $employee->id,
            'component_type' => $data['component_type'],
            'label'          => $data['label'] ?? null,
            'amount'         => $data['amount'],
            'effective_from' => $data['effective_from'],
            'effective_to'   => null,
        ]);

        return back()->with('success', 'Salary component updated.');
    }

    public function destroyComponent(Employee $employee, EmployeeSalaryComponent $component)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);
        abort_if($component->employee_id !== $employee->id, 403);
        $component->delete();

        return back()->with('success', 'Component removed.');
    }

    // ── Monthly Disbursements ──────────────────────────────────────────────

    public function disbursements(Employee $employee)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);

        $instituteId = $this->instituteId();
        AccountingSetupService::bootstrapInstitute($instituteId);

        $disbursements = EmployeeSalaryDisbursement::with(['expenseAccount', 'bankAccount', 'journalEntry'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('year')->orderByDesc('month')
            ->paginate(24);

        $expenseAccounts = Account::where('institute_id', $instituteId)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->get();

        $bankAccounts = InstituteBankAccount::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $settings = FinanceSetting::where('institute_id', $instituteId)->first();

        // Active components snapshot for the form
        $activeComponents = $employee->currentSalaryComponents()->get();
        $totalComponents  = (float) $activeComponents->sum('amount');
        $ctc              = round((float) $employee->basic_salary + $totalComponents, 2);

        return view('institute.employees.salary.disbursements', compact(
            'employee', 'disbursements', 'expenseAccounts',
            'bankAccounts', 'settings', 'activeComponents', 'ctc'
        ));
    }

    public function storeDisbursement(Request $request, Employee $employee)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);

        $instituteId = $this->instituteId();

        $data = $request->validate([
            'month'              => ['required', 'integer', 'min:1', 'max:12'],
            'year'               => ['required', 'integer', 'min:2020'],
            'deductions'         => ['nullable', 'numeric', 'min:0'],
            'expense_account_id' => ['required', 'integer'],
            'payment_mode'       => ['required', 'in:cash,bank'],
            'bank_account_id'    => ['nullable', 'integer', 'required_if:payment_mode,bank'],
            'payment_date'       => ['required', 'date'],
            'notes'              => ['nullable', 'string', 'max:300'],
        ]);

        // Validate expense account belongs to institute
        $expenseAccount = Account::where('institute_id', $instituteId)
            ->where('type', 'expense')
            ->findOrFail((int) $data['expense_account_id']);

        // Prevent duplicate disbursement
        $exists = EmployeeSalaryDisbursement::where('employee_id', $employee->id)
            ->where('month', $data['month'])
            ->where('year', $data['year'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error',
                date('F', mktime(0, 0, 0, $data['month'], 1)) . ' ' . $data['year'] .
                ' ka salary record pehle se exist karta hai.'
            );
        }

        // Auto-calculate from active salary components
        $activeComponents = $employee->currentSalaryComponents()->get();
        $totalAllowances  = round((float) $activeComponents->sum('amount'), 2);
        $basicPaid        = round((float) $employee->basic_salary, 2);
        $grossSalary      = round($basicPaid + $totalAllowances, 2);
        $deductions       = round((float) ($data['deductions'] ?? 0), 2);
        $netSalary        = round($grossSalary - $deductions, 2);

        abort_if($netSalary < 0, 422, 'Deductions cannot exceed gross salary.');

        // Resolve payment account
        $paymentAccountId = null;
        $bankAccountId    = null;

        if ($data['payment_mode'] === 'cash') {
            $settings = FinanceSetting::where('institute_id', $instituteId)->first();
            abort_if(!$settings?->cash_account_id, 422, 'Cash account mapping missing in finance settings.');
            $paymentAccountId = $settings->cash_account_id;
        } else {
            $bank = InstituteBankAccount::where('institute_id', $instituteId)
                ->where('is_active', true)
                ->findOrFail((int) $data['bank_account_id']);
            abort_if(!$bank->gl_account_id, 422, 'Selected bank account GL mapping is missing.');
            $bankAccountId    = $bank->id;
            $paymentAccountId = $bank->gl_account_id;
        }

        // Snapshot of components used at disbursement time
        $snapshot = $activeComponents->map(fn ($c) => [
            'type'   => $c->component_type,
            'label'  => $c->display_label ?? $c->label ?? $c->component_type,
            'amount' => (float) $c->amount,
        ])->values()->toArray();

        $disbursement = EmployeeSalaryDisbursement::create([
            'institute_id'        => $instituteId,
            'employee_id'         => $employee->id,
            'month'               => (int) $data['month'],
            'year'                => (int) $data['year'],
            'basic_paid'          => $basicPaid,
            'total_allowances'    => $totalAllowances,
            'gross_salary'        => $grossSalary,
            'deductions'          => $deductions,
            'net_salary'          => $netSalary,
            'payment_date'        => $data['payment_date'],
            'payment_mode'        => $data['payment_mode'],
            'status'              => 'paid',
            'remarks'             => $data['notes'] ?? null,
            'expense_account_id'  => (int) $expenseAccount->id,
            'payment_account_id'  => $paymentAccountId,
            'bank_account_id'     => $bankAccountId,
            'components_snapshot' => $snapshot,
        ]);

        // Post journal entry
        $journalEntry = JournalService::safePostEmployeeDisbursement(
            $disbursement->fresh(['employee', 'expenseAccount', 'paymentAccount', 'bankAccount'])
        );
        if ($journalEntry) {
            $disbursement->update(['journal_entry_id' => $journalEntry->id]);
        }

        // Debit institute wallet
        InstituteWalletService::debitEmployeeDisbursement($disbursement->fresh(['employee']));

        return back()->with('success',
            '₹' . number_format($netSalary, 2) . ' salary saved for ' .
            $employee->name . ' (' . date('F Y', mktime(0, 0, 0, $data['month'], 1, $data['year'])) . ').' .
            ($journalEntry ? ' Journal entry posted.' : ' Accounting posting pending.')
        );
    }

    public function destroyDisbursement(Employee $employee, EmployeeSalaryDisbursement $disbursement)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);
        abort_if($disbursement->employee_id !== $employee->id, 403);
        abort_if($disbursement->journal_entry_id, 422, 'Cannot delete a disbursement that has been posted to accounts.');

        $disbursement->delete();

        return back()->with('success', 'Disbursement record deleted.');
    }

    // ── Bonuses ────────────────────────────────────────────────────────────

    public function storeBonus(Request $request, Employee $employee)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);

        $data = $request->validate([
            'bonus_type'   => ['required', 'string', 'max:50'],
            'amount'       => ['required', 'numeric', 'min:1'],
            'payment_date' => ['required', 'date'],
            'remarks'      => ['nullable', 'string', 'max:300'],
        ]);

        EmployeeBonus::create([
            'institute_id' => $this->instituteId(),
            'employee_id'  => $employee->id,
            'bonus_type'   => $data['bonus_type'],
            'amount'       => $data['amount'],
            'payment_date' => $data['payment_date'],
            'remarks'      => $data['remarks'] ?? null,
        ]);

        return back()->with('success', 'Bonus recorded.');
    }

    public function destroyBonus(Employee $employee, EmployeeBonus $bonus)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);
        abort_if($bonus->employee_id !== $employee->id, 403);
        $bonus->delete();

        return back()->with('success', 'Bonus deleted.');
    }

    // ── Advances ───────────────────────────────────────────────────────────

    public function storeAdvance(Request $request, Employee $employee)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);

        $data = $request->validate([
            'amount'             => ['required', 'numeric', 'min:1'],
            'given_date'         => ['required', 'date'],
            'recovery_per_month' => ['nullable', 'numeric', 'min:0'],
            'remarks'            => ['nullable', 'string', 'max:300'],
        ]);

        EmployeeAdvance::create([
            'institute_id'       => $this->instituteId(),
            'employee_id'        => $employee->id,
            'amount'             => $data['amount'],
            'given_date'         => $data['given_date'],
            'recovery_per_month' => $data['recovery_per_month'] ?? 0,
            'recovered_amount'   => 0,
            'status'             => 'active',
            'remarks'            => $data['remarks'] ?? null,
        ]);

        return back()->with('success', 'Advance recorded.');
    }

    public function updateAdvanceRecovery(Request $request, Employee $employee, EmployeeAdvance $advance)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);
        abort_if($advance->employee_id !== $employee->id, 403);

        $data = $request->validate(['recovered_amount' => ['required', 'numeric', 'min:0']]);

        $recovered = min((float) $data['recovered_amount'], (float) $advance->amount);
        $advance->update([
            'recovered_amount' => $recovered,
            'status'           => $recovered >= $advance->amount ? 'recovered' : 'active',
        ]);

        return back()->with('success', 'Recovery updated.');
    }
}
