<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Models\InstitutePolicyAcceptance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsentController extends Controller
{
    public function show()
    {
        $user = Auth::guard('web')->user();

        if (InstitutePolicyAcceptance::allAccepted($user->institute_id)) {
            return redirect()->route('institute.dashboard');
        }

        $documents = config('legal.documents');
        $institute = $user->institute;

        return view('institute.consent', compact('documents', 'institute'));
    }

    public function accept(Request $request)
    {
        $user      = Auth::guard('web')->user();
        $documents = config('legal.documents');

        $request->validate([
            'accepted' => 'required|array|size:' . count($documents),
        ]);

        foreach ($documents as $type => $meta) {
            if (!in_array($type, $request->input('accepted', []), true)) {
                return back()->withErrors(['accepted' => 'Please read and accept every document before continuing.']);
            }
        }

        foreach ($documents as $type => $meta) {
            InstitutePolicyAcceptance::updateOrCreate(
                [
                    'institute_id'  => $user->institute_id,
                    'document_type' => $type,
                    'version'       => $meta['version'],
                ],
                [
                    'accepted_by' => $user->id,
                    'accepted_at' => now(),
                    'ip_address'  => $request->ip(),
                ]
            );
        }

        return redirect()->route('institute.dashboard');
    }
}
