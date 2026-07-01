<?php

namespace App\Http\Controllers\Institute\Employee;

use App\Models\EmployeeDepartment;
use Illuminate\Http\Request;

class EmployeeDepartmentController extends EmployeeBaseController
{
    public function index()
    {
        $departments = EmployeeDepartment::where('institute_id', $this->instituteId())
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        return view('institute.employees.departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        EmployeeDepartment::create([
            'institute_id' => $this->instituteId(),
            'name'         => trim($data['name']),
            'status'       => true,
        ]);

        return back()->with('success', 'Department added.');
    }

    public function update(Request $request, EmployeeDepartment $department)
    {
        abort_if($department->institute_id !== $this->instituteId(), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $department->update(['name' => trim($data['name'])]);

        return back()->with('success', 'Department updated.');
    }

    public function destroy(EmployeeDepartment $department)
    {
        abort_if($department->institute_id !== $this->instituteId(), 403);
        abort_if($department->employees()->exists(), 422, 'Department has employees. Remove them first.');
        $department->delete();

        return back()->with('success', 'Department deleted.');
    }

    public function toggle(EmployeeDepartment $department)
    {
        abort_if($department->institute_id !== $this->instituteId(), 403);
        $department->update(['status' => !$department->status]);

        return back()->with('success', 'Status updated.');
    }
}
