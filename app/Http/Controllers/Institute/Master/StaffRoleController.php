<?php
namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\StaffRole;
use Illuminate\Http\Request;

class StaffRoleController extends Controller
{
    public function index()
    {
        $roles = StaffRole::where('institute_id', auth()->user()->institute_id)
            ->withCount('staffMembers')->get();
        return view('institute.master.staff.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = StaffRole::permissionLabels();
        $permissionGroups = StaffRole::permissionGroups();
        return view('institute.master.staff.roles.create', compact('permissions', 'permissionGroups'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:50']);

        $perms = [];
        foreach (array_keys(StaffRole::permissionLabels()) as $key) {
            $perms[$key] = $request->boolean("perm_{$key}");
        }

        StaffRole::create([
            'institute_id' => auth()->user()->institute_id,
            'name'         => strtoupper($request->name),
            'is_system'    => false,
            'permissions'  => $perms,
            'status'       => true,
        ]);

        return redirect()->route('master.staff-roles.index')
            ->with('success', 'Role created!');
    }

    public function edit(StaffRole $staffRole)
    {
        abort_if($staffRole->institute_id !== auth()->user()->institute_id, 403);
        $permissions = StaffRole::permissionLabels();
        $permissionGroups = StaffRole::permissionGroups();
        return view('institute.master.staff.roles.edit', compact('staffRole', 'permissions', 'permissionGroups'));
    }

    public function update(Request $request, StaffRole $staffRole)
    {
        abort_if($staffRole->institute_id !== auth()->user()->institute_id, 403);

        $request->validate(['name' => 'required|string|max:50']);

        $perms = [];
        foreach (array_keys(StaffRole::permissionLabels()) as $key) {
            $perms[$key] = $request->boolean("perm_{$key}");
        }

        $staffRole->update(['name' => strtoupper($request->name), 'permissions' => $perms]);
        return redirect()->route('master.staff-roles.index')->with('success', 'Role updated!');
    }

    public function destroy(StaffRole $staffRole)
    {
        abort_if($staffRole->institute_id !== auth()->user()->institute_id, 403);

        if ($staffRole->staffMembers()->exists()) {
            return redirect()->route('master.staff-roles.index')
                ->with('error', 'This role is assigned to staff members, so it cannot be deleted.');
        }

        $staffRole->delete();
        return redirect()->route('master.staff-roles.index')->with('success', 'Role deleted!');
    }
}
