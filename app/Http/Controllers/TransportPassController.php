<?php

namespace App\Http\Controllers;

use App\Models\Institute;
use App\Models\Student;
use App\Support\SignedPublicLink;
use Illuminate\Http\Request;

class TransportPassController extends Controller
{
    /**
     * Public, unauthenticated status page reached by scanning a transport pass QR
     * code. Deliberately shows only operational information (route, stop, vehicle,
     * driver, active/inactive) — never fee or wallet data — since this page is
     * reachable by anyone holding or photographing a physical pass, not just the
     * student or institute staff.
     */
    public function status(Request $request)
    {
        $sid = (int) $request->sid;
        $iid = (int) $request->iid;
        $sig = (string) $request->sig;

        abort_if(!$sid || !$iid || !$sig, 404);
        abort_if(!SignedPublicLink::verify($sid, $iid, 'transport', $sig), 403, 'Invalid or expired pass link.');

        $student = Student::where('institute_id', $iid)->findOrFail($sid);
        $institute = Institute::findOrFail($iid);

        // A student whose enrollment itself is no longer active (withdrawn, pending,
        // etc.) should never show as "Active" here, even if their transport allocation
        // row was never separately closed — the pass reflects real standing, not just
        // whatever the allocation table happens to still say.
        $allocation = $student->status === 'active'
            ? $student->activeTransportAllocation()->with(['route', 'stop', 'vehicle', 'driver'])->first()
            : null;

        return view('transport.pass-status', compact('student', 'institute', 'allocation'));
    }
}
