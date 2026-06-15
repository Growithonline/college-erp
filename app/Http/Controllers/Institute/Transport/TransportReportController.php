<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\AcademicSession;
use App\Models\TransportAllocation;
use App\Models\TransportPayment;
use App\Models\TransportRoute;
use App\Models\TransportVehicle;
use Illuminate\Http\Request;

class TransportReportController extends TransportBaseController
{
    public function index()
    {
        return view('institute.transport.reports.index');
    }

    public function routeStudents(Request $request)
    {
        $instituteId = $this->instituteId();

        $routes = TransportRoute::where('institute_id', $instituteId)->orderBy('name')->get();
        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $activeSession = $sessions->firstWhere('is_active', true);

        $sessionId = $request->filled('session_id') ? (int) $request->session_id : $activeSession?->id;
        $routeId   = $request->filled('route_id') ? (int) $request->route_id : null;

        $query = TransportAllocation::with([
                'student:id,name,roll_no,mobile,father_name',
                'route:id,name,route_code',
                'stop:id,transport_route_id,stop_name,sequence',
                'vehicle:id,vehicle_no',
                'driver:id,name',
            ])
            ->where('institute_id', $instituteId)
            ->where('is_active', true);

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }
        if ($routeId) {
            $query->where('transport_route_id', $routeId);
        }

        $allocations = $query->orderBy('transport_route_id')
            ->orderBy('transport_route_stop_id')
            ->get();

        $grouped = $allocations->groupBy(fn ($a) => $a->route?->name ?? 'No Route');

        return view('institute.transport.reports.route-students', compact(
            'routes', 'sessions', 'sessionId', 'routeId', 'grouped'
        ));
    }

    public function due(Request $request)
    {
        $instituteId = $this->instituteId();
        $sessions    = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $activeSession = $sessions->firstWhere('is_active', true);
        $routes      = TransportRoute::where('institute_id', $instituteId)->orderBy('name')->get();

        $sessionId = $request->filled('session_id') ? (int) $request->session_id : $activeSession?->id;
        $routeId   = $request->filled('route_id') ? (int) $request->route_id : null;

        $query = TransportAllocation::with([
                'student:id,name,roll_no,mobile',
                'route:id,name',
                'stop:id,transport_route_id,stop_name',
            ])
            ->where('institute_id', $instituteId)
            ->where('is_active', true)
            ->whereRaw('COALESCE(fee_amount, 0) > COALESCE(paid_amount, 0)');

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }
        if ($routeId) {
            $query->where('transport_route_id', $routeId);
        }

        $allocations = $query->orderByDesc(\DB::raw('fee_amount - paid_amount'))->get();

        $totalDue = $allocations->sum(fn ($a) => max(0, (float) $a->fee_amount - (float) $a->paid_amount));

        return view('institute.transport.reports.due', compact(
            'routes', 'sessions', 'sessionId', 'routeId', 'allocations', 'totalDue'
        ));
    }

    public function collection(Request $request)
    {
        $instituteId = $this->instituteId();
        $sessions    = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $activeSession = $sessions->firstWhere('is_active', true);
        $routes      = TransportRoute::where('institute_id', $instituteId)->orderBy('name')->get();

        $sessionId = $request->filled('session_id') ? (int) $request->session_id : $activeSession?->id;
        $routeId   = $request->filled('route_id') ? (int) $request->route_id : null;
        $dateFrom  = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo    = $request->date_to   ?? now()->toDateString();

        $query = TransportPayment::with([
                'student:id,name,roll_no',
                'allocation.route:id,name',
                'allocation.stop:id,transport_route_id,stop_name',
            ])
            ->where('institute_id', $instituteId)
            ->where('is_reversed', false)
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }
        if ($routeId) {
            $query->whereHas('allocation', fn ($q) => $q->where('transport_route_id', $routeId));
        }

        $payments = $query->orderByDesc('payment_date')->orderByDesc('id')->get();

        $totalCollected = $payments->sum('amount');
        $byMode = $payments->groupBy('payment_mode')
            ->map(fn ($g) => ['count' => $g->count(), 'amount' => $g->sum('amount')]);

        return view('institute.transport.reports.collection', compact(
            'routes', 'sessions', 'sessionId', 'routeId',
            'dateFrom', 'dateTo', 'payments', 'totalCollected', 'byMode'
        ));
    }

    public function occupancy(Request $request)
    {
        $instituteId = $this->instituteId();
        $sessions    = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $activeSession = $sessions->firstWhere('is_active', true);

        $sessionId = $request->filled('session_id') ? (int) $request->session_id : $activeSession?->id;

        $vehicles = TransportVehicle::with(['vehicleType'])
            ->withCount(['allocations as active_students' => fn ($q) => $q
                ->where('is_active', true)
                ->when($sessionId, fn ($q2) => $q2->where('academic_session_id', $sessionId))
            ])
            ->where('institute_id', $instituteId)
            ->where('status', true)
            ->orderBy('vehicle_no')
            ->get()
            ->map(function ($v) {
                $v->occupancy_pct = $v->capacity > 0
                    ? min(100, round($v->active_students / $v->capacity * 100))
                    : null;
                return $v;
            });

        return view('institute.transport.reports.occupancy', compact('vehicles', 'sessions', 'sessionId'));
    }
}
