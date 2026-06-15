<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\NoticeRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PartnerNoticeController extends Controller
{
    private function partner()
    {
        return Auth::guard('partner')->user();
    }

    public function index()
    {
        $partner = $this->partner();
        $notices = Notice::forRole($partner->institute_id, 'channel')
            ->paginate(20);

        return view('partner.notices.index', compact('notices', 'partner'));
    }

    public function markRead(Request $request, Notice $notice)
    {
        $partner = $this->partner();

        NoticeRead::firstOrCreate([
            'notice_id'   => $notice->id,
            'reader_type' => 'partner',
            'reader_id'   => $partner->id,
        ], ['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
