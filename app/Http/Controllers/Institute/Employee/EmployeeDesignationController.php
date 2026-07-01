<?php

namespace App\Http\Controllers\Institute\Employee;

use App\Models\EmployeeDepartment;
use App\Models\EmployeeDesignation;
use Illuminate\Http\Request;

class EmployeeDesignationController extends EmployeeBaseController
{
    public function index()
    {
        $designations = EmployeeDesignation::with('department')
            ->where('institute_id', $this->instituteId())
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        $departments = EmployeeDepartment::where('institute_id', $this->instituteId())
            ->where('status', true)->orderBy('name')->get();

        return view('institute.employees.designations.index', compact('designations', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                    => ['required', 'string', 'max:100'],
            'employee_department_id'  => ['nullable', 'exists:employee_departments,id'],
        ]);

        EmployeeDesignation::create([
            'institute_id'           => $this->instituteId(),
            'employee_department_id' => $data['employee_department_id'] ?? null,
            'name'                   => trim($data['name']),
            'status'                 => true,
        ]);

        return back()->with('success', 'Designation added.');
    }

    public function update(Request $request, EmployeeDesignation $designation)
    {
        abort_if($designation->institute_id !== $this->instituteId(), 403);

        $data = $request->validate([
            'name'                   => ['required', 'string', 'max:100'],
            'employee_department_id' => ['nullable', 'exists:employee_departments,id'],
        ]);

        $designation->update([
            'employee_department_id' => $data['employee_department_id'] ?? null,
            'name'                   => trim($data['name']),
        ]);

        return back()->with('success', 'Designation updated.');
    }

    public function destroy(EmployeeDesignation $designation)
    {
        abort_if($designation->institute_id !== $this->instituteId(), 403);
        abort_if($designation->employees()->exists(), 422, 'Designation has employees. Remove them first.');
        $designation->delete();

        return back()->with('success', 'Designation deleted.');
    }
}
