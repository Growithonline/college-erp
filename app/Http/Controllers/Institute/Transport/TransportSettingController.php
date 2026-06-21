<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\InstituteTransportSetting;
use Illuminate\Http\Request;

class TransportSettingController extends TransportBaseController
{
    public function index()
    {
        $setting = InstituteTransportSetting::forInstitute($this->instituteId());

        return view('institute.transport.settings.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'on_route_transfer'        => ['required', 'in:full_charge,no_charge,prorated_charge'],
            'prorated_billing'         => ['required', 'in:disabled,after_midmonth,daily_basis'],
            'yearly_fee_cross_session' => ['nullable', 'boolean'],
        ]);

        InstituteTransportSetting::updateOrCreate(
            ['institute_id' => $this->instituteId()],
            [
                'on_route_transfer'        => $data['on_route_transfer'],
                'prorated_billing'         => $data['prorated_billing'],
                'yearly_fee_cross_session' => $request->boolean('yearly_fee_cross_session'),
            ]
        );

        return back()->with('success', 'Transport settings saved successfully.');
    }
}
