<?php

namespace App\Http\Controllers\Institute\Library;

use App\Http\Controllers\Controller;
use App\Mail\LibraryStaffWelcomeMail;
use App\Models\LibraryLoginLog;
use App\Models\LibraryStaff;
use App\Models\LibraryStaffActivityLog;
use App\Models\LibraryStaffPermission;
use App\Models\StaffMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\InstituteMailer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class LibraryStaffController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index()
    {
        $staff = LibraryStaff::with('permissionRecord', 'staffMember')
            ->where('institute_id', $this->instituteId())
            ->orderBy('name')
            ->get();

        return view('institute.library.staff.index', compact('staff'));
    }

    public function create()
    {
        $staffMembers = StaffMember::where('institute_id', $this->instituteId())
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('institute.library.staff.create', compact('staffMembers'));
    }

    public function store(Request $request)
    {
        $instituteId = $this->instituteId();

        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'email'            => 'required|email|max:150|unique:library_staff,email',
            'phone'            => 'required|string|max:20|unique:library_staff,phone',
            'photo'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'gender'           => 'nullable|in:male,female,other',
            'date_of_birth'    => 'nullable|date|before:today',
            'address'          => 'nullable|string|max:300',
            'designation'      => 'required|in:librarian,assistant_librarian,attendant,data_entry',
            'joining_date'     => 'nullable|date',
            'shift'            => 'required|in:morning,evening,both',
            'assigned_section' => 'nullable|string|max:100',
            'qualification'    => 'nullable|string|max:100',
            'staff_member_id'  => ['nullable', 'integer', Rule::exists('staff_members', 'id')->where('institute_id', $instituteId)],
            'preset'           => 'nullable|in:full_librarian,attendant,data_entry,read_only,custom',
            'permissions'      => 'nullable|array',
            'permissions.*'    => 'string',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')
                ->store("library-staff/photos/{$instituteId}", 'public');
        }

        $staff = null;

        DB::transaction(function () use ($data, $instituteId, $photoPath, &$staff) {
            $staff = LibraryStaff::create([
                'institute_id'     => $instituteId,
                'employee_id'      => LibraryStaff::generateEmployeeId($instituteId),
                'name'             => $data['name'],
                'email'            => $data['email'],
                'phone'            => $data['phone'],
                'photo'            => $photoPath,
                'gender'           => $data['gender'] ?? null,
                'date_of_birth'    => $data['date_of_birth'] ?? null,
                'address'          => $data['address'] ?? null,
                'designation'      => $data['designation'],
                'joining_date'     => $data['joining_date'] ?? null,
                'shift'            => $data['shift'],
                'assigned_section' => $data['assigned_section'] ?? null,
                'qualification'    => $data['qualification'] ?? null,
                'status'           => true,
                'staff_member_id'  => $data['staff_member_id'] ?? null,
            ]);

            $preset      = $data['preset'] ?? 'custom';
            $permissions = $preset !== 'custom'
                ? (LibraryStaff::PRESETS[$preset] ?? [])
                : ($data['permissions'] ?? []);

            LibraryStaffPermission::create([
                'library_staff_id' => $staff->id,
                'preset'           => $preset,
                'permissions'      => $permissions,
            ]);
        });

        // Welcome email — non-fatal if it fails
        if ($staff) {
            try {
                InstituteMailer::send(
                    auth()->user()->institute_id,
                    $staff->email,
                    new LibraryStaffWelcomeMail($staff, route('library_staff.login'))
                );
            } catch (Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('library.staff.index')
            ->with('success', "Library staff member created. A welcome email has been sent to {$staff->email}.");
    }

    public function edit(LibraryStaff $libraryStaff)
    {
        $this->authorizeStaff($libraryStaff);

        $libraryStaff->load('permissionRecord');

        $staffMembers = StaffMember::where('institute_id', $this->instituteId())
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('institute.library.staff.create', compact('libraryStaff', 'staffMembers'));
    }

    public function update(Request $request, LibraryStaff $libraryStaff)
    {
        $this->authorizeStaff($libraryStaff);
        $instituteId = $this->instituteId();

        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'email'            => ['required', 'email', 'max:150', Rule::unique('library_staff', 'email')->ignore($libraryStaff->id)],
            'phone'            => ['required', 'string', 'max:20', Rule::unique('library_staff', 'phone')->ignore($libraryStaff->id)],
            'photo'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_photo'     => 'nullable|boolean',
            'gender'           => 'nullable|in:male,female,other',
            'date_of_birth'    => 'nullable|date|before:today',
            'address'          => 'nullable|string|max:300',
            'designation'      => 'required|in:librarian,assistant_librarian,attendant,data_entry',
            'joining_date'     => 'nullable|date',
            'shift'            => 'required|in:morning,evening,both',
            'assigned_section' => 'nullable|string|max:100',
            'qualification'    => 'nullable|string|max:100',
            'staff_member_id'  => ['nullable', 'integer', Rule::exists('staff_members', 'id')->where('institute_id', $instituteId)],
            'preset'           => 'nullable|in:full_librarian,attendant,data_entry,read_only,custom',
            'permissions'      => 'nullable|array',
            'permissions.*'    => 'string',
        ]);

        $photoPath = $libraryStaff->photo;

        if ($request->boolean('remove_photo') && $photoPath) {
            Storage::disk('public')->delete($photoPath);
            $photoPath = null;
        }

        if ($request->hasFile('photo')) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $request->file('photo')
                ->store("library-staff/photos/{$instituteId}", 'public');
        }

        DB::transaction(function () use ($data, $libraryStaff, $photoPath) {
            $libraryStaff->update([
                'name'             => $data['name'],
                'email'            => $data['email'],
                'phone'            => $data['phone'],
                'photo'            => $photoPath,
                'gender'           => $data['gender'] ?? null,
                'date_of_birth'    => $data['date_of_birth'] ?? null,
                'address'          => $data['address'] ?? null,
                'designation'      => $data['designation'],
                'joining_date'     => $data['joining_date'] ?? null,
                'shift'            => $data['shift'],
                'assigned_section' => $data['assigned_section'] ?? null,
                'qualification'    => $data['qualification'] ?? null,
                'staff_member_id'  => $data['staff_member_id'] ?? null,
            ]);

            $preset      = $data['preset'] ?? 'custom';
            $permissions = $preset !== 'custom'
                ? (LibraryStaff::PRESETS[$preset] ?? [])
                : ($data['permissions'] ?? []);

            LibraryStaffPermission::updateOrCreate(
                ['library_staff_id' => $libraryStaff->id],
                ['preset' => $preset, 'permissions' => $permissions]
            );
        });

        return redirect()->route('library.staff.index')
            ->with('success', 'Library staff member updated successfully.');
    }

    public function toggle(LibraryStaff $libraryStaff)
    {
        $this->authorizeStaff($libraryStaff);
        $libraryStaff->update(['status' => !$libraryStaff->status]);
        return back()->with('success', 'Staff status updated.');
    }

    public function destroy(LibraryStaff $libraryStaff)
    {
        $this->authorizeStaff($libraryStaff);

        if ($libraryStaff->photo) {
            Storage::disk('public')->delete($libraryStaff->photo);
        }

        $libraryStaff->delete();

        return redirect()->route('library.staff.index')
            ->with('success', 'Library staff member deleted.');
    }

    public function resetLock(LibraryStaff $libraryStaff)
    {
        $this->authorizeStaff($libraryStaff);
        $libraryStaff->update(['login_attempts' => 0, 'locked_until' => null]);
        return back()->with('success', 'Account lock has been cleared.');
    }

    public function loginLogs(Request $request)
    {
        $instituteId = $this->instituteId();

        $staffList = LibraryStaff::where('institute_id', $instituteId)
            ->orderBy('name')->get(['id', 'name', 'employee_id']);

        $query = LibraryLoginLog::whereHas('libraryStaff', fn($q) => $q->where('institute_id', $instituteId))
            ->with('libraryStaff:id,name,employee_id')
            ->latest('created_at');

        if ($request->filled('staff_id')) $query->where('library_staff_id', $request->staff_id);
        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('date'))     $query->whereDate('created_at', $request->date);

        $logs = $query->paginate(30)->withQueryString();

        return view('institute.library.staff.login-logs', compact('logs', 'staffList'));
    }

    public function activityLogs(Request $request)
    {
        $instituteId = $this->instituteId();

        $staffList = LibraryStaff::where('institute_id', $instituteId)
            ->orderBy('name')->get(['id', 'name', 'employee_id']);

        $query = LibraryStaffActivityLog::whereHas('libraryStaff', fn($q) => $q->where('institute_id', $instituteId))
            ->with('libraryStaff:id,name,employee_id')
            ->latest('created_at');

        if ($request->filled('staff_id')) $query->where('library_staff_id', $request->staff_id);
        if ($request->filled('action'))   $query->where('action', $request->action);
        if ($request->filled('date'))     $query->whereDate('created_at', $request->date);

        $logs = $query->paginate(30)->withQueryString();

        return view('institute.library.staff.activity-logs', compact('logs', 'staffList'));
    }

    private function authorizeStaff(LibraryStaff $libraryStaff): void
    {
        abort_if($libraryStaff->institute_id !== $this->instituteId(), 403);
    }
}
