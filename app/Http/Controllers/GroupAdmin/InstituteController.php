<?php

namespace App\Http\Controllers\GroupAdmin;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class InstituteController extends Controller
{
    public function index()
    {
        $groupAdmin = Auth::guard('group_admin')->user();

        $institutes = Institute::where('group_id', $groupAdmin->group_id)
            ->withCount('students')
            ->orderBy('name')
            ->get();

        return view('group_admin.institutes.index', compact('groupAdmin', 'institutes'));
    }

    public function resetPassword(Request $request, Institute $institute)
    {
        $groupAdmin = Auth::guard('group_admin')->user();

        abort_unless(
            $institute->group_id === $groupAdmin->group_id && $groupAdmin->can_reset_institute_password,
            403
        );

        $request->validate([
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = User::where('institute_id', $institute->id)
            ->where('role', 'institute_admin')
            ->firstOrFail();

        $user->update(['password' => Hash::make($request->password)]);

        AuditLogService::log($institute->id, 'institute', 'owner_password_reset', 'Institute-owner password reset by Group Admin.', $user, [
            'reset_by_group_admin_id' => $groupAdmin->id,
            'notify_email'            => $request->boolean('notify_email'),
        ]);

        if ($request->boolean('notify_email')) {
            Mail::mailer('smtp')->raw(
                "Hello {$user->name},\n\n" .
                "Your password for College ERP has been reset by your Group Admin.\n\n" .
                "New Password: {$request->password}\n\n" .
                "Login URL: " . url('/login') . "\n\n" .
                "Please change your password after logging in.",
                fn($m) => $m->to($user->email)->subject('Password Reset — College ERP')
            );
        }

        return back()->with('success', 'Password updated successfully.');
    }
}
