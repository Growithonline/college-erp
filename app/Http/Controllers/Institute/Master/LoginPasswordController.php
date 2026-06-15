<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\StaffMember;
use App\Models\ChannelPartner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Institute Admin — Set login password for Center / Staff / Partner
 * Used from master management pages
 */
class LoginPasswordController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    // ── Center password set ────────────────────────────────────────────
    public function setCenterPassword(Request $request, Center $center)
    {
        abort_if($center->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $center->update(['password' => Hash::make($request->password)]);

        return back()->with('success', "Login password set for center: {$center->name}");
    }

    // ── Staff password set ─────────────────────────────────────────────
    public function setStaffPassword(Request $request, StaffMember $staff)
    {
        abort_if($staff->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $staff->update(['password' => Hash::make($request->password)]);

        return back()->with('success', "Login password set for staff: {$staff->name}");
    }

    // ── Partner password set ───────────────────────────────────────────
    public function setPartnerPassword(Request $request, ChannelPartner $partner)
    {
        abort_if($partner->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $partner->update(['password' => Hash::make($request->password)]);

        return back()->with('success', "Login password set for partner: {$partner->name}");
    }

    // ── Show set password form (modal content / AJAX) ──────────────────
    public function showForm(Request $request, string $type, int $id)
    {
        $instituteId = $this->instituteId();

        $user = match($type) {
            'center'  => Center::where('institute_id', $instituteId)->findOrFail($id),
            'staff'   => StaffMember::where('institute_id', $instituteId)->findOrFail($id),
            'partner' => ChannelPartner::where('institute_id', $instituteId)->findOrFail($id),
            default   => abort(404),
        };

        return view('institute.master.set-password-modal', compact('user', 'type'));
    }
}