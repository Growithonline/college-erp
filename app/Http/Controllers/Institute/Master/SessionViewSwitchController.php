<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use Illuminate\Http\Request;

class SessionViewSwitchController extends Controller
{
    /**
     * Admin ke liye sirf PHP session mein view session store karo.
     * DB ka is_active field touch nahi hota — staff/center/channel unaffected rehte hain.
     */
    public function switch(Request $request)
    {
        $instituteId = auth()->user()->institute_id;

        $sessionId = $request->input('session_id');

        if ($sessionId === null || $sessionId === '') {
            // Reset — wapas DB active session pe
            session()->forget('institute_view_session_id');
        } else {
            $exists = AcademicSession::where('institute_id', $instituteId)
                ->where('id', $sessionId)
                ->exists();

            abort_if(!$exists, 403);

            session(['institute_view_session_id' => (int) $sessionId]);
        }

        $referer = $request->headers->get('referer');
        return $referer ? redirect($referer) : redirect()->route('institute.dashboard');
    }
}
