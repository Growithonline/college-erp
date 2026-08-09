<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupAdmin;
use App\Models\Institute;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::withCount(['institutes', 'groupAdmins'])->orderByDesc('id')->get();
        return view('super_admin.groups.index', compact('groups'));
    }

    public function create()
    {
        return view('super_admin.groups.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
        ]);

        $group = Group::create([
            'name'   => $request->name,
            'status' => true,
        ]);

        return redirect()->route('super_admin.groups.show', $group->id)
            ->with('success', 'Group created successfully.');
    }

    public function show(Group $group)
    {
        $group->loadCount('institutes');
        $institutes = $group->institutes()->withCount('students')->orderBy('name')->get();
        $unassignedInstitutes = Institute::whereNull('group_id')->orderBy('name')->get(['id', 'name']);
        $groupAdmins = $group->groupAdmins()->orderBy('name')->get();

        return view('super_admin.groups.show', compact('group', 'institutes', 'unassignedInstitutes', 'groupAdmins'));
    }

    public function toggle(Group $group)
    {
        $group->update(['status' => !$group->status]);
        return back()->with('success', 'Group status updated.');
    }

    public function update(Request $request, Group $group)
    {
        $request->validate([
            'institute_quota'             => 'nullable|integer|min:1',
            'per_institute_student_limit' => 'nullable|integer|min:1',
            'institute_subscription_type' => 'nullable|in:fixed,lifetime',
            'institute_subscription_end'  => 'required_if:institute_subscription_type,fixed|nullable|date|after:today',
        ]);

        $group->update([
            'institute_quota'             => $request->institute_quota,
            'per_institute_student_limit' => $request->per_institute_student_limit,
            'institute_subscription_type' => $request->institute_subscription_type,
            'institute_subscription_end'  => $request->institute_subscription_type === 'fixed' ? $request->institute_subscription_end : null,
        ]);

        return back()->with('success', 'Group settings updated.');
    }

    public function assignInstitute(Request $request, Group $group)
    {
        $request->validate(['institute_id' => 'required|exists:institutes,id']);

        $institute = Institute::whereNull('group_id')->find($request->institute_id);
        abort_if(!$institute, 422, 'Institute is not available to assign — it may already belong to a group.');

        $institute->update(['group_id' => $group->id]);

        return back()->with('success', "\"{$institute->name}\" assigned to this group.");
    }

    public function storeAdmin(Request $request, Group $group)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => ['required', 'email', 'unique:group_admins,email'],
        ]);

        $plainPassword = Str::random(10);

        $groupAdmin = GroupAdmin::create([
            'group_id' => $group->id,
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($plainPassword),
            'status'   => true,
        ]);

        try {
            Mail::mailer('smtp')->raw(
                "Hello {$groupAdmin->name},\n\n" .
                "You have been given Group Admin access on College ERP for \"{$group->name}\".\n\n" .
                "Email: {$groupAdmin->email}\n" .
                "Password: {$plainPassword}\n\n" .
                "Login URL: " . url('/group-admin/login') . "\n\n" .
                "Please change your password after logging in.",
                fn($m) => $m->to($groupAdmin->email)->subject('Group Admin Access — College ERP')
            );
        } catch (\Throwable $e) {
            \Log::warning('Group admin welcome email failed', ['group_id' => $group->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Group admin created but the credential email could not be delivered. Please check platform SMTP settings.');
        }

        return back()->with('success', 'Group admin created and credentials sent to email.');
    }

    public function resetAdminPassword(Request $request, Group $group, GroupAdmin $groupAdmin)
    {
        abort_unless($groupAdmin->group_id === $group->id, 404);

        $request->validate([
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $groupAdmin->update(['password' => Hash::make($request->password)]);

        AuditLogService::log(null, 'group_admin', 'password_reset', 'Group-Admin password reset by Super Admin.', $groupAdmin, [
            'notify_email' => $request->boolean('notify_email'),
        ]);

        if ($request->boolean('notify_email')) {
            Mail::mailer('smtp')->raw(
                "Hello {$groupAdmin->name},\n\n" .
                "Your password for College ERP Group Admin access has been reset by the Super Admin.\n\n" .
                "New Password: {$request->password}\n\n" .
                "Login URL: " . url('/group-admin/login') . "\n\n" .
                "Please change your password after logging in.",
                fn($m) => $m->to($groupAdmin->email)->subject('Password Reset — College ERP')
            );
        }

        return back()->with('success', 'Group admin password updated successfully.');
    }

    public function toggleAdminStatus(Group $group, GroupAdmin $groupAdmin)
    {
        abort_unless($groupAdmin->group_id === $group->id, 404);

        $groupAdmin->update(['status' => !$groupAdmin->status]);
        return back()->with('success', 'Group admin status updated.');
    }

    public function toggleResetPermission(Group $group, GroupAdmin $groupAdmin)
    {
        abort_unless($groupAdmin->group_id === $group->id, 404);

        $groupAdmin->update(['can_reset_institute_password' => !$groupAdmin->can_reset_institute_password]);
        return back()->with('success', 'Password-reset permission updated.');
    }

    public function toggleCreatePermission(Group $group, GroupAdmin $groupAdmin)
    {
        abort_unless($groupAdmin->group_id === $group->id, 404);

        $groupAdmin->update(['can_create_institutes' => !$groupAdmin->can_create_institutes]);
        return back()->with('success', 'Institute-creation permission updated.');
    }
}
