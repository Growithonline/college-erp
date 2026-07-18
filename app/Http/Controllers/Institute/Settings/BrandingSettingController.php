<?php

namespace App\Http\Controllers\Institute\Settings;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrandingSettingController extends Controller
{
    private function institute(): Institute
    {
        return Institute::findOrFail(Auth::user()->institute_id);
    }

    public function index()
    {
        $institute = $this->institute();
        return view('institute.settings.branding', compact('institute'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $this->institute()->update(['primary_color' => $request->primary_color]);

        return back()->with('success', 'Brand color updated.');
    }
}
