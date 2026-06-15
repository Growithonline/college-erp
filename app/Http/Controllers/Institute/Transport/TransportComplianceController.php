<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\TransportVehicle;

class TransportComplianceController extends TransportBaseController
{
    public function index()
    {
        $vehicles = TransportVehicle::where('institute_id', $this->instituteId())
            ->orderBy('vehicle_no')
            ->get()
            ->map(function (TransportVehicle $vehicle) {
                $docs = [
                    'insurance' => $vehicle->insurance_expiry,
                    'permit' => $vehicle->permit_expiry,
                    'fitness' => $vehicle->fitness_expiry,
                    'pollution' => $vehicle->pollution_expiry,
                ];

                $expiringSoon = collect($docs)->filter(function ($date) {
                    return $date && $date->between(now()->startOfDay(), now()->addDays(30)->endOfDay());
                })->keys()->values()->all();

                $expired = collect($docs)->filter(fn ($date) => $date && $date->lt(now()->startOfDay()))->keys()->values()->all();

                $vehicle->compliance_expiring = $expiringSoon;
                $vehicle->compliance_expired = $expired;
                $vehicle->compliance_status = empty($expired) ? (empty($expiringSoon) ? 'ok' : 'warning') : 'expired';

                return $vehicle;
            });

        return view('institute.transport.compliance.index', compact('vehicles'));
    }
}
