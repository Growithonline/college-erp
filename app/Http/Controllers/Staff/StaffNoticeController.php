<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Institute\NoticeController as InstituteNoticeController;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffNoticeController extends Controller
{
    private function staff()
    {
        return Auth::guard('staff')->user();
    }

    private function requireManagePermission(): void
    {
        if (!$this->staff()->can_manage_notices) {
            abort(403, 'Aapko notices manage karne ki permission nahi hai.');
        }
    }

    public function index(Request $request)
    {
        $staff = $this->staff();

        // Staff with manage permission → admin-style management table
        if ($staff->can_manage_notices) {
            return app(InstituteNoticeController::class)->index($request);
        }

        // Others → card-based read-only view (like center/partner)
        $notices = Notice::forRole($staff->institute_id, 'staff')
            ->paginate(20)
            ->withQueryString();

        return view('staff.notices.index', compact('notices', 'staff'));
    }

    public function create()
    {
        $this->requireManagePermission();
        return app(InstituteNoticeController::class)->create();
    }

    public function store(Request $request)
    {
        $this->requireManagePermission();
        return app(InstituteNoticeController::class)->store($request);
    }

    public function edit(Notice $notice)
    {
        $this->requireManagePermission();
        return app(InstituteNoticeController::class)->edit($notice);
    }

    public function update(Request $request, Notice $notice)
    {
        $this->requireManagePermission();
        return app(InstituteNoticeController::class)->update($request, $notice);
    }

    public function destroy(Notice $notice)
    {
        $this->requireManagePermission();
        return app(InstituteNoticeController::class)->destroy($notice);
    }

    public function toggle(Notice $notice)
    {
        $this->requireManagePermission();
        return app(InstituteNoticeController::class)->toggle($notice);
    }

    public function pin(Notice $notice)
    {
        $this->requireManagePermission();
        return app(InstituteNoticeController::class)->pin($notice);
    }

    public function markRead(Notice $notice)
    {
        \App\Models\NoticeRead::firstOrCreate([
            'notice_id'   => $notice->id,
            'reader_type' => 'staff',
            'reader_id'   => $this->staff()->id,
        ], ['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function readDetail(Notice $notice)
    {
        $this->requireManagePermission();
        return app(InstituteNoticeController::class)->readDetail($notice);
    }
}
