<?php

namespace App\Http\Controllers\Institute\Employee;

use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeBonus;
use App\Models\EmployeeSalaryComponent;
use App\Models\EmployeeSalaryDisbursement;
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

        $disbursements = EmployeeSalaryDisbursement::where('employee_id', $employee->id)
            ->orderByDesc('year')->orderByDesc('month')
            ->paginate(24);

        return view('institute.employees.salary.disbursements', compact('employee', 'disbursements'));
    }

    public function storeDisbursement(Request $request, Employee $employee)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);

        $data = $request->validate([
            'month'        => ['required', 'integer', 'min:1', 'max:12'],
            'year'         => ['required', 'integer', 'min:2020'],
            'gross_amount' => ['required', 'numeric', 'min:0'],
            'deductions'   => ['nullable', 'numeric', 'min:0'],
            'paid_on'      => ['nullable', 'date'],
            'notes'        => ['nullable', 'string', 'max:300'],
        ]);

        $gross = (float) $data['gross_amount'];
        $ded   = (float) ($data['deductions'] ?? 0);
        $net   = $gross - $ded;

        EmployeeSalaryDisbursement::updateOrCreate(
            ['employee_id' => $employee->id, 'month' => $data['month'], 'year' => $data['year']],
            [
                'institute_id'     => $this->instituteId(),
                'basic_paid'       => $gross,
                'total_allowances' => 0,
                'gross_salary'     => $gross,
                'deductions'       => $ded,
                'net_salary'       => $net,
                'payment_date'     => $data['paid_on'] ?? null,
                'status'           => isset($data['paid_on']) ? 'paid' : 'pending',
                'remarks'          => $data['notes'] ?? null,
            ]
        );

        return back()->with('success', 'Salary record saved.');
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
            'amount'              => ['required', 'numeric', 'min:1'],
            'given_date'          => ['required', 'date'],
            'recovery_per_month'  => ['nullable', 'numeric', 'min:0'],
            'remarks'             => ['nullable', 'string', 'max:300'],
        ]);

        EmployeeAdvance::create([
            'institute_id'        => $this->instituteId(),
            'employee_id'         => $employee->id,
            'amount'              => $data['amount'],
            'given_date'          => $data['given_date'],
            'recovery_per_month'  => $data['recovery_per_month'] ?? 0,
            'recovered_amount'    => 0,
            'status'              => 'active',
            'remarks'             => $data['remarks'] ?? null,
        ]);

        return back()->with('success', 'Advance recorded.');
    }

    public function updateAdvanceRecovery(Request $request, Employee $employee, EmployeeAdvance $advance)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);
        abort_if($advance->employee_id !== $employee->id, 403);

        $data = $request->validate(['recovered_amount' => ['required', 'numeric', 'min:0']]);

        $recovered = min((float)$data['recovered_amount'], (float)$advance->amount);
        $advance->update([
            'recovered_amount' => $recovered,
            'status'           => $recovered >= $advance->amount ? 'recovered' : 'active',
        ]);

        return back()->with('success', 'Recovery updated.');
    }
}
