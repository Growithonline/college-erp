<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\AcademicSession;
use App\Models\FeeInvoice;
use App\Models\FeeInvoiceItem;
use App\Models\InstituteTransportSetting;
use App\Models\Student;
use App\Models\TransportAllocation;
use App\Models\TransportDriver;
use App\Models\TransportRoute;
use App\Models\TransportRouteAssignment;
use App\Models\TransportRouteStop;
use App\Models\TransportVehicle;
use App\Services\StudentIdService;
use App\Services\WalletService;
use Carbon\Carbon;
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
            $s = $this->escapeLike(trim((string) $request->student));
            $query->whereHas('student', function ($studentQuery) use ($s) {
                $studentQuery->where('name', 'like', '%' . $s . '%')
                    ->orWhere('roll_no', 'like', '%' . $s . '%');
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
        $this->assertStopSelectedIfRequired($route->id, $stop?->id);

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
        $setting  = InstituteTransportSetting::forInstitute($this->instituteId());

        // Full route change history for this student
        $history = TransportAllocation::with(['route:id,name,route_code', 'stop:id,transport_route_id,stop_name', 'session:id,name'])
            ->where('student_id', $allocation->student_id)
            ->where('institute_id', $this->instituteId())
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        return view('institute.transport.allocations.show', compact('allocation', 'payments', 'routes', 'history', 'setting'));
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

    public function pass(Request $request, TransportAllocation $allocation)
    {
        $this->assertInstituteModel($allocation);
        $allocation->load(['student', 'route', 'stop', 'vehicle', 'driver', 'session']);

        $institute = \App\Models\Institute::findOrFail($this->instituteId());
        $qrSvg = $this->generatePassQr($allocation->student_id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('institute.transport.allocations.pass', compact(
            'allocation', 'institute', 'qrSvg'
        ))->setPaper([0, 0, 243, 153]); // ~85.6mm x 54mm (ID-1 card size) in points, already landscape-shaped

        $filename = 'transport-pass-' . ($allocation->student?->roll_no ?? $allocation->id) . '.pdf';

        // ?view=1 opens inline in the browser instead of forcing a download — lets
        // staff preview a pass without saving/printing it every single time.
        return $request->boolean('view') ? $pdf->stream($filename) : $pdf->download($filename);
    }

    public function bulkPass(Request $request)
    {
        $data = $request->validate([
            'route_id'   => ['nullable', Rule::exists('transport_routes', 'id')->where('institute_id', $this->instituteId())],
            'session_id' => ['nullable', Rule::exists('academic_sessions', 'id')->where('institute_id', $this->instituteId())],
        ]);

        $query = TransportAllocation::where('institute_id', $this->instituteId())
            ->where('is_active', true)
            ->with(['student', 'route', 'stop', 'vehicle', 'driver', 'session']);

        if (!empty($data['route_id'])) {
            $query->where('transport_route_id', $data['route_id']);
        }
        if (!empty($data['session_id'])) {
            $query->where('academic_session_id', $data['session_id']);
        }

        $allocations = $query->orderBy('student_id')->get();
        abort_if($allocations->isEmpty(), 404, 'No active allocations found for the selected filters.');

        $institute = \App\Models\Institute::findOrFail($this->instituteId());

        $passes = $allocations->map(fn (TransportAllocation $a) => [
            'allocation' => $a,
            'qrSvg'      => $this->generatePassQr($a->student_id),
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('institute.transport.allocations.pass-bulk', compact(
            'passes', 'institute'
        ))->setPaper([0, 0, 243, 153]);

        $filename = 'transport-passes-' . now()->format('Ymd-His') . '.pdf';

        return $request->boolean('view') ? $pdf->stream($filename) : $pdf->download($filename);
    }

    /**
     * QR payload is only student_id + institute_id (HMAC-signed via SignedPublicLink)
     * — never a specific allocation — so scanning always resolves whatever is
     * currently active at scan time. A printed card stays valid across route changes
     * without needing to be reprinted. SVG, not PNG: PNG needs the Imagick extension
     * (confirmed not installed here — bacon/bacon-qr-code throws without it), while
     * SVG renders through pure PHP.
     *
     * Returns a `data:image/svg+xml;base64,...` URI for use as a plain <img src="">.
     *
     * This dompdf install (v3.1.5, checked directly in vendor/) has no frame/renderer
     * for a bare inline <svg> element at all — its only SVG support is treating an
     * SVG document as an *image* (via php-svg-lib), the same as any <img> or
     * background-image. Embedding the raw <svg>...</svg> markup straight into the
     * card's HTML — as this used to do — silently rendered nothing: no error, no
     * fallback, just an empty cell where the QR should be. A base64 data URI on an
     * <img> tag is the path dompdf actually supports, and also means the image
     * scales via ordinary <img> width/height like any other image, so there's no
     * need to strip the SVG's own width/height attributes anymore.
     */
    private function generatePassQr(int $studentId): string
    {
        $verifyUrl = \App\Support\SignedPublicLink::url(
            '/transport/pass-status',
            $studentId,
            $this->instituteId(),
            'transport'
        );

        $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(180)->margin(1)->generate($verifyUrl);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function collectPayment(Request $request, TransportAllocation $allocation)
    {
        $this->assertInstituteModel($allocation);

        $balance = max(0, round((float) $allocation->balance, 2));

        $data = $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:' . $balance],
            'payment_date' => ['required', 'date'],
            'payment_mode' => ['required', 'in:cash,upi,online,cheque'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'note'         => ['nullable', 'string'],
        ], [
            'amount.max' => "Amount cannot exceed balance ₹{$balance}.",
        ]);

        abort_if($balance <= 0, 422, 'No pending balance on this allocation.');

        $allocation->loadMissing(['student', 'route']);
        $requestedAmount = round((float) $data['amount'], 2);

        $invoice = null;
        $amount  = 0.0;
        DB::transaction(function () use ($allocation, $data, $requestedAmount, &$invoice, &$amount) {
            // Lock the allocation row and re-derive the balance fresh — guards against a
            // double-submit / concurrent-collect race. Without this, the wallet could be
            // credited for an amount that no longer has any balance left to settle.
            $locked = TransportAllocation::where('id', $allocation->id)->lockForUpdate()->first();
            $liveBalance = round((float) $locked->balance, 2);
            $amount = min($requestedAmount, max(0, $liveBalance));
            if ($amount <= 0) {
                return;
            }

            $instituteId = $allocation->institute_id;
            $studentId   = $allocation->student_id;
            $sessionId   = $allocation->academic_session_id;
            $routeName   = $allocation->route?->name ?? '';

            // Same pattern as the Fee Collection page and Transport Billing's one-time
            // collect: create a FeeInvoice so this payment shows up in Admin Income,
            // All Collections, Fee History, and (for cheque payments) the cheque
            // clearance tracker — not just the wallet ledger.
            $invoiceNo = StudentIdService::generateInvoiceId($instituteId, now()->year);

            $invoice = FeeInvoice::create([
                'institute_id'          => $instituteId,
                'student_id'            => $studentId,
                'academic_session_id'   => $sessionId,
                'semester'              => $allocation->student?->current_semester ?? 1,
                'invoice_no'            => $invoiceNo,
                'total_amount'          => $amount,
                'discount'              => 0,
                'paid_amount'           => $amount,
                'payment_mode'          => $data['payment_mode'],
                'transaction_ref'       => $data['reference_no'] ?? null,
                'payment_date'          => $data['payment_date'],
                'payment_datetime'      => now(),
                'remarks'               => $data['note'] ?? ('Transport fee — ' . $routeName),
                'collected_by'          => auth()->guard('staff')->user()?->name ?? auth()->user()?->name ?? 'Staff',
                'collected_by_staff_id' => auth()->guard('staff')->id(),
                'is_cancelled'          => false,
                'remaining_due'         => 0,
            ]);

            FeeInvoiceItem::create([
                'fee_invoice_id' => $invoice->id,
                'item_type'      => 'transport',
                'fee_name'       => 'Transport Fee — ' . $routeName,
                'amount'         => $amount,
                'discount'       => 0,
                'fine'           => 0,
                'total_fee'      => $amount,
            ]);

            // Wallet CREDIT + institute income + journal posting + cheque tracking —
            // identical path the Fee Collection page uses.
            WalletService::onFeeCollection($invoice);

            // Settle transport allocation — creates TransportPayment + updates paid_amount/status
            WalletService::settleTransportFromInvoice($allocation->id, $amount, $invoice->id, auth()->id());
        });

        if (!$invoice) {
            return back()->with('success', 'Fee already fully collected.');
        }

        return back()->with('success', 'Transport payment recorded successfully.');
    }

    public function close(Request $request, TransportAllocation $allocation)
    {
        $this->assertInstituteModel($allocation);

        $outstanding = max(0, round((float) $allocation->balance, 2));

        $data = $request->validate([
            'cancellation_date' => [
                'required',
                'date',
                'after_or_equal:' . ($allocation->start_date?->toDateString() ?? '1900-01-01'),
                'before_or_equal:today',
            ],
            'credit_amount' => ['nullable', 'numeric', 'min:0', 'max:' . $outstanding],
        ]);

        $requestedCredit = round((float) ($data['credit_amount'] ?? 0), 2);
        $routeName       = $allocation->route?->name ?? 'Route';

        DB::transaction(function () use ($allocation, $data, $requestedCredit, $routeName) {
            // Lock the allocation row and re-derive the outstanding balance fresh —
            // guards against a double-submit / concurrent-cancel race applying two
            // credit notes (or closing an already-closed allocation) against the same row.
            $locked = TransportAllocation::where('id', $allocation->id)->lockForUpdate()->first();

            if (!$locked || !$locked->is_active) {
                return;
            }

            $liveOutstanding = max(0, round((float) $locked->balance, 2));
            $creditAmount    = min($requestedCredit, $liveOutstanding);

            if ($creditAmount > 0) {
                WalletService::creditTransportAllocation(
                    $locked,
                    $creditAmount,
                    'Transport cancellation credit — ' . $routeName
                );
            }

            $locked->update([
                'is_active' => false,
                'status'    => 'closed',
                'end_date'  => $data['cancellation_date'],
            ]);
        });

        return back()->with('success', 'Transport allocation cancelled successfully.');
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

        $resolvedStopId = $data['transport_route_stop_id'] ?? $allocation->transport_route_stop_id;
        $this->assertStopSelectedIfRequired(
            (int) $allocation->transport_route_id,
            $resolvedStopId ? (int) $resolvedStopId : null
        );

        $newFeeAmount = isset($data['fee_amount']) ? (float) $data['fee_amount'] : (float) $allocation->fee_amount;

        DB::transaction(function () use ($allocation, $data, $resolvedStopId, $newFeeAmount) {
            $allocation->update([
                'transport_route_stop_id' => $resolvedStopId,
                // LOW-4: preserve existing vehicle/driver if not submitted (don't silently null them)
                'transport_vehicle_id'    => array_key_exists('transport_vehicle_id', $data) ? $data['transport_vehicle_id'] : $allocation->transport_vehicle_id,
                'transport_driver_id'     => array_key_exists('transport_driver_id', $data) ? $data['transport_driver_id'] : $allocation->transport_driver_id,
                'fee_amount'              => $newFeeAmount,
                'remarks'                 => $data['remarks'] ?? null,
            ]);

            // fee_amount alone is just the reference price — if this allocation was
            // already billed, charged_amount (what's actually owed on the wallet) needs
            // to move too, or Fee Collection / Due Report keep showing the old amount.
            WalletService::adjustTransportAllocationCharge(
                $allocation,
                $newFeeAmount,
                'Fee correction — allocation edited (' . ($allocation->route?->name ?? 'Route') . ')'
            );
        });

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
            'credit_amount'           => ['nullable', 'numeric', 'min:0'],
        ]);

        $newRoute = TransportRoute::findOrFail($data['transport_route_id']);
        $stop     = null;
        if (!empty($data['transport_route_stop_id'])) {
            $stop = TransportRouteStop::where('transport_route_id', $newRoute->id)->findOrFail($data['transport_route_stop_id']);
        }
        $this->assertStopSelectedIfRequired($newRoute->id, $stop?->id);

        $setting = InstituteTransportSetting::forInstitute($this->instituteId());

        // Auto-populate vehicle/driver from active route assignment if not provided in form
        $routeAssignment = TransportRouteAssignment::where('institute_id', $allocation->institute_id)
            ->where('transport_route_id', $newRoute->id)
            ->whereNull('end_date')
            ->first();

        $vehicleId    = $data['transport_vehicle_id'] ?? $routeAssignment?->transport_vehicle_id;
        $driverId     = $data['transport_driver_id']  ?? $routeAssignment?->transport_driver_id;
        $creditAmount = round((float) ($data['credit_amount'] ?? 0), 2);
        $oldRouteName = $allocation->route?->name ?? 'Previous Route';

        DB::transaction(function () use ($allocation, $data, $newRoute, $stop, $setting, $vehicleId, $driverId, $creditAmount, $oldRouteName) {
            // Apply credit note on old allocation before closing it
            if ($creditAmount > 0) {
                WalletService::creditTransportAllocation(
                    $allocation,
                    $creditAmount,
                    "Route transfer credit — {$oldRouteName}"
                );
            }

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
                'transport_vehicle_id'    => $vehicleId,
                'transport_driver_id'     => $driverId,
                'fee_amount'              => $baseFee,   // original fee stored for future billing
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

        $creditNote = $creditAmount > 0 ? ' Credit note of ₹' . number_format($creditAmount, 2) . ' applied on old route.' : '';
        $msg = match ($setting->on_route_transfer) {
            'no_charge'      => 'Route transfer complete. No charge applied as per institute policy.' . $creditNote,
            'prorated_charge'=> 'Route transfer complete. Prorated charge applied for remaining days.' . $creditNote,
            default          => 'Route transfer complete. New allocation created.' . $creditNote,
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
        $this->assertStopSelectedIfRequired($route->id, $stop?->id);

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

    /**
     * A route with priced stops but no stop selected silently resolves to the route's
     * own base fee (often 0 for stop-priced routes), so a forgotten stop selection can
     * create a zero-fee allocation with no warning. Block that specific case — a route
     * with no priced stops at all is unaffected, since its base fee is the real price.
     */
    private function assertStopSelectedIfRequired(int $routeId, ?int $stopId): void
    {
        if ($stopId) {
            return;
        }

        $hasPricedStops = TransportRouteStop::where('transport_route_id', $routeId)
            ->where('status', true)
            ->where('fee_amount', '>', 0)
            ->exists();

        abort_if($hasPricedStops, 422, 'This route has priced stops — please select a stop so the correct fee is charged.');
    }
}
