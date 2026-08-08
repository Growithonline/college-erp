<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\StaffIdService;

class UidBackfillController extends Controller
{
    public function index()
    {
        $counts = StaffIdService::missingUidCounts();
        $lastRun = session('uid_backfill_last_run');

        return view('super_admin.uid_backfill.index', compact('counts', 'lastRun'));
    }

    public function run()
    {
        $result = StaffIdService::backfillMissingUids();
        session(['uid_backfill_last_run' => $result]);

        return redirect()->route('super_admin.uid-backfill.index')
            ->with('success', 'Backfill complete — ' .
                $result['staff'] . ' staff, ' .
                $result['partner'] . ' partners, ' .
                $result['center'] . ' centers, ' .
                $result['library_staff'] . ' library staff updated.');
    }

    public function resetLegacy()
    {
        $result = StaffIdService::resetLegacySixDigitIds();

        return redirect()->route('super_admin.uid-backfill.index')
            ->with('success', 'Reset ' .
                $result['staff'] . ' staff, ' .
                $result['partner'] . ' partners, ' .
                $result['center'] . ' centers with a 6-digit Login ID. Click "Run Backfill" now to regenerate them in the current 4-digit format.');
    }
}
