<?php

namespace App\Http\Controllers\Institute\Employee;

use App\Models\Employee;
use App\Models\EmployeeDepartment;
use App\Models\EmployeeDesignation;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends EmployeeBaseController
{
    public function index(Request $request)
    {
        $instituteId = $this->instituteId();
        $query = Employee::with(['department', 'designation'])
            ->where('institute_id', $instituteId);

        if ($request->filled('department')) {
            $query->where('employee_department_id', $request->department);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('employee_code', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $employees   = $query->orderBy('name')->paginate(20)->withQueryString();
        $departments = EmployeeDepartment::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();

        return view('institute.employees.index', compact('employees', 'departments'));
    }

    public function create()
    {
        $instituteId  = $this->instituteId();
        $departments  = EmployeeDepartment::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $designations = EmployeeDesignation::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();

        return view('institute.employees.create', compact('departments', 'designations'));
    }

    public function store(Request $request)
    {
        $data = $this->validateEmployee($request);
        $instituteId = $this->instituteId();

        $photo = null;
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo')->store('employees/photos', 'public');
        }

        $employee = Employee::create([
            'institute_id'            => $instituteId,
            'employee_department_id'  => $data['employee_department_id'] ?? null,
            'employee_designation_id' => $data['employee_designation_id'] ?? null,
            'employee_code'           => $data['employee_code'] ?? null,
            'name'                    => $data['name'],
            'father_name'             => $data['father_name'] ?? null,
            'dob'                     => $data['dob'] ?? null,
            'gender'                  => $data['gender'] ?? null,
            'blood_group'             => $data['blood_group'] ?? null,
            'phone'                   => $data['phone'] ?? null,
            'alternate_phone'         => $data['alternate_phone'] ?? null,
            'email'                   => $data['email'] ?? null,
            'address'                 => $data['address'] ?? null,
            'city'                    => $data['city'] ?? null,
            'state'                   => $data['state'] ?? null,
            'pincode'                 => $data['pincode'] ?? null,
            'photo'                   => $photo,
            'joining_date'            => $data['joining_date'] ?? null,
            'employment_type'         => $data['employment_type'],
            'salary_type'             => $data['salary_type'],
            'basic_salary'            => $data['basic_salary'] ?? 0,
            'status'         => $data['status'],
            'notes'          => $data['notes'] ?? null,
            'license_no'     => $data['license_no'] ?? null,
            'license_expiry' => $data['license_expiry'] ?? null,
        ]);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee added successfully.');
    }

    public function show(Employee $employee)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);
        $employee->load([
            'department', 'designation', 'documents',
            'salaryComponents', 'disbursements',
            'bonuses', 'advances',
        ]);

        return view('institute.employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);
        $instituteId  = $this->instituteId();
        $departments  = EmployeeDepartment::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $designations = EmployeeDesignation::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();

        return view('institute.employees.edit', compact('employee', 'departments', 'designations'));
    }

    public function update(Request $request, Employee $employee)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);
        $data = $this->validateEmployee($request, $employee->id);

        $photo = $employee->photo;
        if ($request->hasFile('photo')) {
            if ($photo) Storage::disk('public')->delete($photo);
            $photo = $request->file('photo')->store('employees/photos', 'public');
        }

        $employee->update([
            'employee_department_id'  => $data['employee_department_id'] ?? null,
            'employee_designation_id' => $data['employee_designation_id'] ?? null,
            'employee_code'           => $data['employee_code'] ?? null,
            'name'                    => $data['name'],
            'father_name'             => $data['father_name'] ?? null,
            'dob'                     => $data['dob'] ?? null,
            'gender'                  => $data['gender'] ?? null,
            'blood_group'             => $data['blood_group'] ?? null,
            'phone'                   => $data['phone'] ?? null,
            'alternate_phone'         => $data['alternate_phone'] ?? null,
            'email'                   => $data['email'] ?? null,
            'address'                 => $data['address'] ?? null,
            'city'                    => $data['city'] ?? null,
            'state'                   => $data['state'] ?? null,
            'pincode'                 => $data['pincode'] ?? null,
            'photo'                   => $photo,
            'joining_date'            => $data['joining_date'] ?? null,
            'employment_type'         => $data['employment_type'],
            'salary_type'             => $data['salary_type'],
            'basic_salary'            => $data['basic_salary'] ?? 0,
            'status'         => $data['status'],
            'notes'          => $data['notes'] ?? null,
            'license_no'     => $data['license_no'] ?? null,
            'license_expiry' => $data['license_expiry'] ?? null,
        ]);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);
        if ($employee->photo) Storage::disk('public')->delete($employee->photo);
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee deleted.');
    }

    // ── Documents ──────────────────────────────────────────────────────────

    public function storeDocument(Request $request, Employee $employee)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);

        $data = $request->validate([
            'document_type'   => ['required', 'string', 'max:50'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'issue_date'      => ['nullable', 'date'],
            'expiry_date'     => ['nullable', 'date'],
            'file'            => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'notes'           => ['nullable', 'string', 'max:300'],
        ]);

        $path = null;
        $originalName = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store("employees/{$employee->id}/documents", 'public');
            $originalName = $request->file('file')->getClientOriginalName();
        }

        EmployeeDocument::create([
            'institute_id'    => $this->instituteId(),
            'employee_id'     => $employee->id,
            'document_type'   => $data['document_type'],
            'document_number' => $data['document_number'] ?? null,
            'issue_date'      => $data['issue_date'] ?? null,
            'expiry_date'     => $data['expiry_date'] ?? null,
            'file_path'       => $path,
            'original_name'   => $originalName,
            'notes'           => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function destroyDocument(Employee $employee, EmployeeDocument $document)
    {
        abort_if($employee->institute_id !== $this->instituteId(), 403);
        abort_if($document->employee_id !== $employee->id, 403);

        if ($document->file_path) Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }

    // ── Private ────────────────────────────────────────────────────────────

    private function validateEmployee(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'employee_department_id'  => ['nullable', 'exists:employee_departments,id'],
            'employee_designation_id' => ['nullable', 'exists:employee_designations,id'],
            'employee_code'           => ['nullable', 'string', 'max:30'],
            'name'                    => ['required', 'string', 'max:120'],
            'father_name'             => ['nullable', 'string', 'max:120'],
            'dob'                     => ['nullable', 'date'],
            'gender'                  => ['nullable', 'in:male,female,other'],
            'blood_group'             => ['nullable', 'string', 'max:5'],
            'phone'                   => ['nullable', 'string', 'max:15'],
            'alternate_phone'         => ['nullable', 'string', 'max:15'],
            'email'                   => ['nullable', 'email', 'max:150'],
            'address'                 => ['nullable', 'string'],
            'city'                    => ['nullable', 'string', 'max:80'],
            'state'                   => ['nullable', 'string', 'max:80'],
            'pincode'                 => ['nullable', 'string', 'max:10'],
            'photo'                   => ['nullable', 'image', 'max:2048'],
            'joining_date'            => ['nullable', 'date'],
            'employment_type'         => ['required', 'in:full_time,part_time,contractual,daily_wage'],
            'salary_type'             => ['required', 'in:monthly,daily_wage'],
            'basic_salary'            => ['nullable', 'numeric', 'min:0'],
            'status'          => ['required', 'in:active,inactive,terminated,resigned'],
            'notes'           => ['nullable', 'string'],
            'license_no'      => ['nullable', 'string', 'max:80'],
            'license_expiry'  => ['nullable', 'date'],
        ]);
    }
}
