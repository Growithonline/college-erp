@extends('institute.layout')
@section('title', 'Edit Employee')
@section('breadcrumb', 'Employees / Edit')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Edit Employee</h4>
        <p class="text-muted mb-0" style="font-size:13px;">{{ $employee->name }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('employees.show', $employee) }}" class="btn btn-light btn-sm px-3">
            <i class="bi bi-eye me-1"></i> View
        </a>
        <a href="{{ route('employees.index') }}" class="btn btn-light btn-sm px-3">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger py-2 mb-4">
        <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="row g-4">
        {{-- Personal Details --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <h6 class="fw-semibold mb-0"><i class="bi bi-person me-2 text-primary"></i>Personal Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $employee->name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Father's Name</label>
                            <input type="text" class="form-control" name="father_name" value="{{ old('father_name', $employee->father_name) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Date of Birth</label>
                            <input type="date" class="form-control" name="dob" value="{{ old('dob', $employee->dob?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Gender</label>
                            <select class="form-select" name="gender">
                                <option value="">—</option>
                                @foreach(['male','female','other'] as $g)
                                    <option value="{{ $g }}" {{ old('gender', $employee->gender) === $g ? 'selected' : '' }}>{{ ucfirst($g) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Blood Group</label>
                            <select class="form-select" name="blood_group">
                                <option value="">—</option>
                                @foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg)
                                    <option {{ old('blood_group', $employee->blood_group) === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone', $employee->phone) }}" maxlength="10" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Alternate Phone</label>
                            <input type="text" class="form-control" name="alternate_phone" value="{{ old('alternate_phone', $employee->alternate_phone) }}" maxlength="10">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email', $employee->email) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Address</label>
                            <textarea class="form-control" name="address" rows="2">{{ old('address', $employee->address) }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">City</label>
                            <input type="text" class="form-control" name="city" value="{{ old('city', $employee->city) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">State</label>
                            <input type="text" class="form-control" name="state" value="{{ old('state', $employee->state) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Pincode</label>
                            <input type="text" class="form-control" name="pincode" value="{{ old('pincode', $employee->pincode) }}" maxlength="6">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Photo</label>
                            @if($employee->photo)
                                <div class="mb-1">
                                    <img src="{{ Storage::url($employee->photo) }}" class="rounded" width="40" height="40" style="object-fit:cover;">
                                </div>
                            @endif
                            <input type="file" class="form-control" name="photo" accept="image/*">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Employment Details --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <h6 class="fw-semibold mb-0"><i class="bi bi-briefcase me-2 text-primary"></i>Employment Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Employee Code</label>
                            <input type="text" class="form-control" name="employee_code" value="{{ old('employee_code', $employee->employee_code) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Department</label>
                            <select class="form-select" name="employee_department_id">
                                <option value="">— Select —</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('employee_department_id', $employee->employee_department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Designation</label>
                            <select class="form-select" name="employee_designation_id">
                                <option value="">— Select —</option>
                                @foreach($designations as $desig)
                                    <option value="{{ $desig->id }}" {{ old('employee_designation_id', $employee->employee_designation_id) == $desig->id ? 'selected' : '' }}>{{ $desig->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Joining Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="joining_date" value="{{ old('joining_date', $employee->joining_date?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Employment Type</label>
                            <select class="form-select" name="employment_type">
                                @foreach(['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contractual' => 'Contractual', 'daily_wage' => 'Daily Wage'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('employment_type', $employee->employment_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Salary Type</label>
                            <select class="form-select" name="salary_type">
                                <option value="monthly"    {{ old('salary_type', $employee->salary_type) === 'monthly'    ? 'selected' : '' }}>Monthly</option>
                                <option value="daily_wage" {{ old('salary_type', $employee->salary_type) === 'daily_wage' ? 'selected' : '' }}>Daily Wage</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Basic Salary (₹)</label>
                            <input type="number" class="form-control" name="basic_salary" value="{{ old('basic_salary', $employee->basic_salary) }}" min="0" step="0.01">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Status</label>
                            <select class="form-select" name="status">
                                @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'terminated' => 'Terminated', 'resigned' => 'Resigned'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('status', $employee->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Notes</label>
                            <textarea class="form-control" name="notes" rows="2">{{ old('notes', $employee->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 d-flex gap-2 justify-content-end">
            <a href="{{ route('employees.index') }}" class="btn btn-light px-4">Cancel</a>
            <button type="submit" class="btn btn-primary px-5">
                <i class="bi bi-floppy me-1"></i> Update Employee
            </button>
        </div>
    </div>
</form>
@endsection
