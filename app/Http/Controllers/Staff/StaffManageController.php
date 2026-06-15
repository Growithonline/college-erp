<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use App\Models\StaffRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffManageController extends Controller
{
    private function staff()
    {
        return Auth::guard('staff')->user();
    }

    private function permCheck(): void
    {
        if (!$this->staff()->canManageStaff()) {
            abort(403, 'Permission denied.');
        }
    }

    public function index(Request $request)
    {
        $this->permCheck();
        $instituteId = $this->staff()->institute_id;

        $roles = StaffRole::where('institute_id', $instituteId)
            ->where('status', true)->orderBy('name')->get();

        $members = StaffMember::with('role')
            ->where('institute_id', $instituteId)
            ->when($request->role_id, fn($q) => $q->where('staff_role_id', $request->role_id))
            ->when($request->search, function ($q) use ($request) {
                $search = trim((string) $request->search);

                $q->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->boolean('status')))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('staff.staff-manage.index', compact('members', 'roles'));
    }

    public function show(StaffMember $staffMember)
    {
        $this->permCheck();
        abort_if($staffMember->institute_id !== $this->staff()->institute_id, 403);

        $staffMember->load('role');

        return view('staff.staff-manage.show', compact('staffMember'));
    }
}
