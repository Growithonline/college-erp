<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;

class EmbedCodesController extends Controller
{
    public function index()
    {
        // The institute always comes from the logged-in account, never from the request.
        $institute = auth()->guard('web')->user()->institute;
        abort_unless($institute, 404);

        return view('institute.embed-codes', compact('institute'));
    }
}
