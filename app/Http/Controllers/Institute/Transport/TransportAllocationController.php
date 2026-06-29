<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\AcademicSession;
use App\Models\InstituteTransportSetting;
use App\Models\Student;
use App\Models\TransportAllocation;
use App\Models\TransportDriver;
use App\Models\TransportPayment;
use App\Models\TransportRoute;
use App\Models\TransportRouteStop;
use App\Models\TransportVehicle;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransportAllocationController extends TransportBaseController
{
    public function index(Request $request)
    {
        $query = TransportAllocation::with(['student:id,name,roll_no', 'session:id,name', 'route:id,name,route_code', 'stop:id,transport_route_id,stop_name', 'vehicle:id,vehicle_no', 'driver:id,name'])
            ->where('institute_id', $this->instituteId())
            ->orderByDesc('is_active')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('route_id')) {
            $query->where('transport_route_id', $request->integer('route_id'));
        }

        if ($request->filled('student')) {
            $query->whereHas('student', function ($studentQuery) use ($request) {
                $studentQuery->where('name', 'like', '%' . $request->student . '%')
                    ->orWhere('roll_no', 'like', '%' . $request->student . '%');
            });
        }

        if ($request->filled('session_id')) {
            $query->where('academic_session_id', $request->integer('session_id'));
        }

        $allocations = $query->paginate(20)->withQueryString();
        $routes   = TransportRoute::where('institute_id', $this->instituteId())->orderBy('name')->get();
        $sessions = AcademicSession::where('institute_id', $this->instituteId())->orderByDesc('id')->get();

        return view('institute.transport.allocations.index', compact('allocations', 'routes', 'sessions'));
    }

    public function create()
    {
        $students = Student::where('institute_id', $this->instituteId())
            ->where('status', '!=', 'pending')
            ->orderBy('name')
            ->get(['id', 'name', 'roll_no']);
        $routes = TransportRoute::with('stops')
            ->where('institute_id', $this->instituteId())
            ->where('status', true)
            ->orderBy('name')
            ->get();
        $stops = TransportRouteStop::with('route:id,name')
            ->whereHas('route', function ($query) {
                $query->where('institute_id', $this->instituteId());
            })
            ->where('status', true)
            ->orderBy('sequence')
            ->get();
        $vehicles = TransportVehicle::where('institute_id', $this->instituteId())->where('status', true)->orderBy('vehicle_no')->get();
        $drivers = TransportDriver::where('institute_id', $this->instituteId())->where('status', true)->orderBy('name')->get();
        $sessions = AcademicSession::where('institute_id', $this->instituteId())->orderByDesc('id')->get();

        return view('institute.transport.allocations.create', compact('students', 'routes', 'stops', 'vehicles', 'drivers', 'sessions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where('institute_id', $this->instituteId())],
            'academic_session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('institute_id', $this->instituteId())],
            'transport_route_id' => ['required', Rule::exists('transport_routes', 'id')->where('institute_id', $this->instituteId())],
            'transport_route_stop_id' => ['nullable', Rule::exists('transport_route_stops', 'id')],
            'transport_vehicle_id' => ['nullable', Rule::exists('transport_vehicles', 'id')->where('institute_id', $this->instituteId())],
            'transport_driver_id' => ['nullable', Rule::exists('transport_drivers', 'id')->where('institute_id', $this->instituteId())],
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
            'charge_now' => ['nullable', 'boolean'],
        ]);

        $student = Student::where('institute_id', $this->instituteId())->findOrFail($data['student_id']);
        abort_if(
            $student->status === 'pending',
            422,
            "This student's admission is pending approval. Transport cannot be assigned until the admission is approved."
        );

        $route = TransportRoute::where('institute_id', $this->instituteId())->findOrFail($data['transport_route_id']);
        $stop = null;
        if (!empty($data['transport_route_stop_id'])) {
            $stop = TransportRouteStop::where('transport_route_id', $route->id)->findOrFail($data['transport_route_stop_id']);
        }

        if (!empty($data['transport_vehicle_id'])) {
            $vehicle = TransportVehicle::where('institute_id', $this->instituteId())->findOrFail($data['transport_vehicle_id']);
            abort_if(!$vehicle->status, 422, 'Selected vehicle is inactive.');
        }

        if (!empty($data['transport_driver_id'])) {
            $driver = TransportDriver::where('institute_id', $this->instituteId())->findOrFail($data['transport_driver_id']);
            abort_if(!$driver->status, 422, 'Selected driver is inactive.');
        }

        $existing = TransportAllocation::where('student_id', $data['student_id'])
            ->where('academic_session_id', $data['academic_session_id'])
            ->where('is_active', true)
            ->first();

        DB::transaction(function () use ($data, $route, $stop, $existing) {
            if ($existing) {
                $existing->update([
                    'is_active' => false,
                    'status' => 'closed',
                    'end_date' => now()->toDateString(),
                ]);
            }

            $allocation = TransportAllocation::create([
                'student_id' => $data['student_id'],
                'institute_id' => $this->instituteId(),
                'academic_session_id' => $data['academic_session_id'],
                'transport_route_id' => $route->id,
                'transport_route_stop_id' => $stop?->id,
                'transport_vehicle_id' => $data['transport_vehicle_id'] ?? null,
                'transport_driver_id' => $data['transport_driver_id'] ?? null,
                'fee_amount' => (float) ($data['fee_amount'] ?? ($stop?->fee_amount > 0 ? $stop->fee_amount : $route->fee_amount)),
                'charged_amount' => 0,
                'paid_amount' => 0,
                'start_date' => $data['start_date'],
                'status' => 'active',
                'is_active' => true,
                'remarks' => $data['remarks'] ?? null,
            ]);

            if ((bool) ($data['charge_now'] ?? true)) {
                WalletService::chargeTransportAllocation($allocation);
            }
        });

        return redirect()->route('transport.allocations.index')->with('success', 'Transport allocation saved successfully.');
    }

    public function show(TransportAllocation $allocation)
    {
        $this->assertInstituteModel($allocation);
        $allocation->load(['student:id,name,roll_no,mobile', 'session', 'route', 'stop', 'vehicle', 'driver', 'payments.transaction']);

        $payments = $allocation->payments()->latest('id')->get();
        $routes   = TransportRoute::where('institute_id', $this->instituteId())->where('status', true)->orderBy('name')->get();

        // Full route change history for this student
        $history = TransportAllocation::with(['route:id,name,route_code', 'stop:id,transport_route_id,stop_name', 'session:id,name'])
            ->where('student_id', $allocation->student_id)
            ->where('institute_id', $this->instituteId())
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        return view('institute.transport.allocations.show', compact('allocation', 'payments', 'routes', 'history'));
    }

    public function pdf(TransportAllocation $allocation)
    {
        $this->assertInstituteModel($allocation);
        $allocation->load(['student.stream.course', 'session', 'route', 'stop', 'vehicle', 'driver', 'payments']);

        $institute = \App\Models\Institute::findOrFail($this->instituteId());
        $payments  = $allocation->payments()->where('is_reversed', false)->latest('id')->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('institute.transport.allocations.pdf', compact(
            'allocation', 'payments', 'institute'
        ))->setPaper('a5', 'portrait');

        $filename = 'transport-' . ($allocation->student?->roll_no ?? $allocation->id) . '.pdf';
        return $pdf->download($filename);
    }

    public function collectPayment(Request $request, TransportAllocation $allocation)
    {
        $this->assertInstituteModel($allocation);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_mode' => ['required', 'in:cash,upi,online,cheque'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
        ]);

        abort_if((float) $data['amount'] > max(0, (float) $allocation->balance), 422, 'Amount exceeds pending balance.');

        DB::transaction(function () use ($allocation, $data) {
            $payment = TransportPayment::create([
                'transport_allocation_id' => $allocation->id,
                'student_id' => $allocation->student_id,
                'institute_id' => $allocation->institute_id,
                'academic_session_id' => $allocation->academic_session_id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_mode' => $data['payment_mode'],
                'reference_no' => $data['reference_no'] ?? null,
                'note' => $data['note'] ?? null,
                'by_user_id' => auth()->id(),
            ]);

            WalletService::collectTransportPayment($payment);
        });

        return back()->with('success', 'Transport payment recorded successfully.');
    }

    public function close(TransportAllocation $allocation)
    {
        $this->assertInstituteModel($allocation);

        $allocation->update([
            'is_active' => false,
            'status'    => 'closed',
            'end_date'  => now()->toDateString(),
        ]);

        return back()->with('success', 'Transport allocation closed.');
    }

    // ── EDIT ────────────────────────────────────────────────────────────────
    public function edit(TransportAllocation $allocation)
    {
        $this->assertInstituteModel($allocation);
        $allocation->load(['student', 'route', 'stop', 'vehicle', 'driver']);

        $vehicles = TransportVehicle::where('institute_id', $this->instituteId())->where('status', true)->orderBy('vehicle_no')->get();
        $drivers  = TransportDriver::where('institute_id', $this->instituteId())->where('status', true)->orderBy('name')->get();
        $stops    = TransportRouteStop::where('transport_route_id', $allocation->transport_route_id)->where('status', true)->orderBy('sequence')->get();

        return view('institute.transport.allocations.edit', compact('allocation', 'vehicles', 'drivers', 'stops'));
    }

    public function update(Request $request, TransportAllocation $allocation)
    {
        $this->assertInstituteModel($allocation);

        $data = $request->validate([
            // HIGH-3: scope stop to the allocation's current route (prevents cross-institute stop injection)
            'transport_route_stop_id' => ['nullable', Rule::exists('transport_route_stops', 'id')
                ->where('transport_route_id', $allocation->transport_route_id)],
            'transport_vehicle_id'    => ['nullable', Rule::exists('transport_vehicles', 'id')->where('institute_id', $this->instituteId())],
            'transport_driver_id'     => ['nullable', Rule::exists('transport_drivers', 'id')->where('institute_id', $this->instituteId())],
            'fee_amount'              => ['nullable', 'numeric', 'min:0'],
            'remarks'                 => ['nullable', 'string'],
        ]);

        $allocation->update([
            'transport_route_stop_id' => $data['transport_route_stop_id'] ?? $allocation->transport_route_stop_id,
            // LOW-4: preserve existing vehicle/driver if not submitted (don't silently null them)
            'transport_vehicle_id'    => array_key_exists('transport_vehicle_id', $data) ? $data['transport_vehicle_id'] : $allocation->transport_vehicle_id,
            'transport_driver_id'     => array_key_exists('transport_driver_id', $data) ? $data['transport_driver_id'] : $allocation->transport_driver_id,
            'fee_amount'              => isset($data['fee_amount']) ? (float) $data['fee_amount'] : $allocation->fee_amount,
            'remarks'                 => $data['remarks'] ?? null,
        ]);

        return redirect()->route('transport.allocations.show', $allocation)->with('success', 'Allocation updated.');
    }

    // ── ROUTE TRANSFER ──────────────────────────────────────────────────────
    public function transfer(Request $request, TransportAllocation $allocation)
    {
        $this->assertInstituteModel($allocation);

        $data = $request->validate([
            'transport_route_id'      => ['required', Rule::exists('transport_routes', 'id')->where('institute_id', $this->instituteId())],
            'transport_route_stop_id' => ['nullable', Rule::exists('transport_route_stops', 'id')],
            'transport_vehicle_id'    => ['nullable', Rule::exists('transport_vehicles', 'id')->where('institute_id', $this->instituteId())],
            'transport_driver_id'     => ['nullable', Rule::exists('transport_drivers', 'id')->where('institute_id', $this->instituteId())],
            'fee_amount'              => ['nullable', 'numeric', 'min:0'],
            'start_date'              => ['required', 'date'],
        ]);

        $newRoute = TransportRoute::findOrFail($data['transport_route_id']);
        $stop     = null;
        if (!empty($data['transport_route_stop_id'])) {
            $stop = TransportRouteStop::where('transport_route_id', $newRoute->id)->findOrFail($data['transport_route_stop_id']);
        }

        $setting = InstituteTransportSetting::forInstitute($this->instituteId());

        DB::transaction(function () use ($allocation, $data, $newRoute, $stop, $setting) {
            $allocation->update([
                'is_active' => false,
                'status'    => 'closed',
                'end_date'  => now()->toDateString(),
            ]);

            $baseFee   = (float) ($data['fee_amount'] ?? ($stop?->fee_amount > 0 ? $stop->fee_amount : $newRoute->fee_amount));
            $feeAmount = $baseFee;

            // Apply institute transfer policy to the fee charged on the new route
            if ($setting->noChargeOnTransfer()) {
                $feeAmount = 0.0;
            } elseif ($setting->proratesOnTransfer()) {
                // Remaining days in current month × daily rate
                $startDate  = \Carbon\Carbon::parse($data['start_date']);
                $monthEnd   = $startDate->copy()->endOfMonth();
                $totalDays  = $monthEnd->day;
                $remaining  = $totalDays - $startDate->day + 1;
                $feeAmount  = round(($remaining / $totalDays) * $baseFee, 2);
            }

            $newAllocation = TransportAllocation::create([
                'student_id'              => $allocation->student_id,
                'institute_id'            => $allocation->institute_id,
                'academic_session_id'     => $allocation->academic_session_id,
                'transport_route_id'      => $newRoute->id,
                'transport_route_stop_id' => $stop?->id,
                'transport_vehicle_id'    => $data['transport_vehicle_id'] ?? null,
                'transport_driver_id'     => $data['transport_driver_id'] ?? null,
                'fee_amount'              => $baseFee,   // original fee stored for future billing
                'charged_amount'          => 0,
                'paid_amount'             => 0,
                'start_date'              => $data['start_date'],
                'status'                  => 'active',
                'is_active'               => true,
            ]);

            // Only charge if there's something to charge
            if ($feeAmount > 0) {
                // HIGH-2 fix: pass prorated amount as override instead of mutating the model's fee_amount
                $chargeOverride = (abs($feeAmount - $baseFee) > 0.001) ? $feeAmount : null;
                WalletService::chargeTransportAllocation($newAllocation, $chargeOverride);
            }
        });

        $msg = match ($setting->on_route_transfer) {
            'no_charge'      => 'Route transfer complete. No charge applied as per institute policy.',
            'prorated_charge'=> 'Route transfer complete. Prorated charge applied for remaining days.',
            default          => 'Route transfer complete. New allocation created.',
        };

        return redirect()->route('transport.allocations.index')->with('success', $msg);
    }

    // ── BULK ALLOCATION ─────────────────────────────────────────────────────
    public function bulkCreate()
    {
        $routes   = TransportRoute::with('stops')->where('institute_id', $this->instituteId())->where('status', true)->orderBy('name')->get();
        $vehicles = TransportVehicle::where('institute_id', $this->instituteId())->where('status', true)->orderBy('vehicle_no')->get();
        $drivers  = TransportDriver::where('institute_id', $this->instituteId())->where('status', true)->orderBy('name')->get();
        $sessions = AcademicSession::where('institute_id', $this->instituteId())->orderByDesc('id')->get();
        $students = Student::where('institute_id', $this->instituteId())
            ->where('status', '!=', 'pending')
            ->whereDoesntHave('transportAllocations', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name', 'father_name', 'mother_name', 'mobile', 'roll_no', 'enrollment_no', 'uin_no']);

        return view('institute.transport.allocations.bulk', compact('routes', 'vehicles', 'drivers', 'sessions', 'students'));
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'academic_session_id'     => ['required', Rule::exists('academic_sessions', 'id')->where('institute_id', $this->instituteId())],
            'transport_route_id'      => ['required', Rule::exists('transport_routes', 'id')->where('institute_id', $this->instituteId())],
            'transport_route_stop_id' => ['nullable', Rule::exists('transport_route_stops', 'id')],
            'transport_vehicle_id'    => ['nullable', Rule::exists('transport_vehicles', 'id')->where('institute_id', $this->instituteId())],
            'transport_driver_id'     => ['nullable', Rule::exists('transport_drivers', 'id')->where('institute_id', $this->instituteId())],
            'fee_amount'              => ['nullable', 'numeric', 'min:0'],
            'start_date'              => ['required', 'date'],
            'student_ids'             => ['required', 'array', 'min:1'],
            'student_ids.*'           => ['integer', Rule::exists('students', 'id')->where('institute_id', $this->instituteId())],
        ]);

        $route  = TransportRoute::findOrFail($data['transport_route_id']);
        $stop   = !empty($data['transport_route_stop_id'])
            ? TransportRouteStop::where('transport_route_id', $route->id)->find($data['transport_route_stop_id'])
            : null;

        $feeAmount = (float) ($data['fee_amount'] ?? ($stop?->fee_amount > 0 ? $stop->fee_amount : $route->fee_amount));
        $count = 0;
        $skipped = 0;

        DB::transaction(function () use ($data, $route, $stop, $feeAmount, &$count, &$skipped) {
            foreach ($data['student_ids'] as $studentId) {
                $studentStatus = Student::where('id', $studentId)
                    ->where('institute_id', $this->instituteId())
                    ->value('status');

                if ($studentStatus === 'pending') {
                    $skipped++;
                    continue;
                }

                $hasActive = TransportAllocation::where('student_id', $studentId)
                    ->where('academic_session_id', $data['academic_session_id'])
                    ->where('is_active', true)
                    ->exists();

                if ($hasActive) {
                    $skipped++;
                    continue;
                }

                $allocation = TransportAllocation::create([
                    'student_id'              => $studentId,
                    'institute_id'            => $this->instituteId(),
                    'academic_session_id'     => $data['academic_session_id'],
                    'transport_route_id'      => $route->id,
                    'transport_route_stop_id' => $stop?->id,
                    'transport_vehicle_id'    => $data['transport_vehicle_id'] ?? null,
                    'transport_driver_id'     => $data['transport_driver_id'] ?? null,
                    'fee_amount'              => $feeAmount,
                    'charged_amount'          => 0,
                    'paid_amount'             => 0,
                    'start_date'              => $data['start_date'],
                    'status'                  => 'active',
                    'is_active'               => true,
                ]);

                WalletService::chargeTransportAllocation($allocation);
                $count++;
            }
        });

        $msg = "{$count} students allocated successfully.";
        if ($skipped) {
            $msg .= " {$skipped} skipped (already have active allocation).";
        }

        return redirect()->route('transport.allocations.index')->with('success', $msg);
    }
}
