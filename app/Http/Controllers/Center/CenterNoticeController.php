<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\NoticeRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CenterNoticeController extends Controller
{
    private function center()
    {
        return Auth::guard('center')->user();
    }

    public function index()
    {
        $center  = $this->center();
        $notices = Notice::forRole($center->institute_id, 'center')
            ->paginate(20);

        return view('center.notices.index', compact('notices', 'center'));
    }

    public function markRead(Request $request, Notice $notice)
    {
        $center = $this->center();

        NoticeRead::firstOrCreate([
            'notice_id'   => $notice->id,
            'reader_type' => 'center',
            'reader_id'   => $center->id,
        ], ['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
