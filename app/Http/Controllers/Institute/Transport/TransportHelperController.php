<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\TransportHelper;
use Illuminate\Http\Request;

class TransportHelperController extends TransportBaseController
{
    public function index()
    {
        $helpers = TransportHelper::where('institute_id', $this->instituteId())
            ->orderBy('name')
            ->paginate(20);

        return view('institute.transport.helpers.index', compact('helpers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => ['required', 'string', 'max:120'],
            'mobile' => ['nullable', 'digits:10'],
            'notes'  => ['nullable', 'string'],
        ]);

        TransportHelper::create([
            'institute_id' => $this->instituteId(),
            'name'         => trim($data['name']),
            'mobile'       => $data['mobile'] ?? null,
            'notes'        => $data['notes'] ?? null,
            'status'       => true,
        ]);

        return back()->with('success', 'Helper added successfully.');
    }

    public function update(Request $request, TransportHelper $helper)
    {
        $this->assertInstituteModel($helper);

        $data = $request->validate([
            'name'   => ['required', 'string', 'max:120'],
            'mobile' => ['nullable', 'digits:10'],
            'notes'  => ['nullable', 'string'],
        ]);

        $helper->update([
            'name'   => trim($data['name']),
            'mobile' => $data['mobile'] ?? null,
            'notes'  => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Helper updated successfully.');
    }

    public function destroy(TransportHelper $helper)
    {
        $this->assertInstituteModel($helper);
        abort_if(
            $helper->routeAssignments()->whereNull('end_date')->exists(),
            422,
            'Helper is assigned to an active route. Close the assignment first.'
        );
        $helper->delete();

        return back()->with('success', 'Helper deleted successfully.');
    }

    public function toggle(TransportHelper $helper)
    {
        $this->assertInstituteModel($helper);
        $helper->update(['status' => !$helper->status]);

        return back()->with('success', 'Helper status updated.');
    }
}
