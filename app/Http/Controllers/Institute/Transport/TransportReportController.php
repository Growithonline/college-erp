<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Exports\TransportCollectionExport;
use App\Exports\TransportDueExport;
use App\Exports\TransportRouteStudentsExport;
use App\Models\AcademicSession;
use App\Models\TransportAllocation;
use App\Models\TransportPayment;
use App\Models\TransportRoute;
use App\Models\TransportVehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

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

    public function exportRouteStudents(Request $request)
    {
        $instituteId = $this->instituteId();
        $routes      = TransportRoute::where('institute_id', $instituteId)->orderBy('name')->get();
        $sessions    = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $activeSession = $sessions->firstWhere('is_active', true);

        $sessionId = $request->filled('session_id') ? (int) $request->session_id : $activeSession?->id;
        $routeId   = $request->filled('route_id') ? (int) $request->route_id : null;

        $query = TransportAllocation::with([
                'student:id,name,roll_no,mobile,father_name',
                'route:id,name,route_code',
                'stop:id,transport_route_id,stop_name,sequence',
                'vehicle:id,vehicle_no',
            ])
            ->where('institute_id', $instituteId)
            ->where('is_active', true);

        if ($sessionId) $query->where('academic_session_id', $sessionId);
        if ($routeId)   $query->where('transport_route_id', $routeId);

        $allocations = $query->orderBy('transport_route_id')->orderBy('transport_route_stop_id')->get();

        $rows = $allocations->values()->map(fn ($a, $i) => [
            $i + 1,
            $a->route?->name ?? '—',
            $a->stop?->stop_name ?? '—',
            $a->student?->name ?? '—',
            $a->student?->roll_no ?? '—',
            $a->student?->mobile ?? '—',
            $a->student?->father_name ?? '—',
            $a->vehicle?->vehicle_no ?? '—',
            number_format((float) $a->fee_amount, 2),
            number_format((float) $a->paid_amount, 2),
            number_format(max(0, (float) $a->fee_amount - (float) $a->paid_amount), 2),
        ]);

        $session = $sessions->firstWhere('id', $sessionId);
        return Excel::download(
            new TransportRouteStudentsExport(collect($rows)),
            'transport-route-students-' . ($session?->name ?? now()->format('Y-m-d')) . '.xlsx'
        );
    }

    public function exportDue(Request $request)
    {
        $instituteId = $this->instituteId();
        $sessions    = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $activeSession = $sessions->firstWhere('is_active', true);
        $routes      = TransportRoute::where('institute_id', $instituteId)->orderBy('name')->get();

        $sessionId = $request->filled('session_id') ? (int) $request->session_id : $activeSession?->id;
        $routeId   = $request->filled('route_id') ? (int) $request->route_id : null;

        $query = TransportAllocation::with(['student:id,name,roll_no,mobile', 'route:id,name', 'stop:id,transport_route_id,stop_name'])
            ->where('institute_id', $instituteId)
            ->where('is_active', true)
            ->whereRaw('COALESCE(fee_amount, 0) > COALESCE(paid_amount, 0)');

        if ($sessionId) $query->where('academic_session_id', $sessionId);
        if ($routeId)   $query->where('transport_route_id', $routeId);

        $allocations = $query->orderByDesc(\DB::raw('fee_amount - paid_amount'))->get();

        $rows = $allocations->values()->map(fn ($a, $i) => [
            $i + 1,
            $a->student?->name ?? '—',
            $a->student?->roll_no ?? '—',
            $a->student?->mobile ?? '—',
            $a->route?->name ?? '—',
            $a->stop?->stop_name ?? '—',
            number_format((float) $a->fee_amount, 2),
            number_format((float) $a->paid_amount, 2),
            number_format(max(0, (float) $a->fee_amount - (float) $a->paid_amount), 2),
            ucfirst($a->status),
        ]);

        return Excel::download(
            new TransportDueExport(collect($rows)),
            'transport-due-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportCollection(Request $request)
    {
        $instituteId = $this->instituteId();
        $sessions    = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $activeSession = $sessions->firstWhere('is_active', true);

        $sessionId = $request->filled('session_id') ? (int) $request->session_id : $activeSession?->id;
        $routeId   = $request->filled('route_id') ? (int) $request->route_id : null;
        $dateFrom  = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo    = $request->date_to   ?? now()->toDateString();

        $query = TransportPayment::with(['student:id,name,roll_no', 'allocation.route:id,name', 'allocation.stop:id,transport_route_id,stop_name'])
            ->where('institute_id', $instituteId)
            ->where('is_reversed', false)
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);

        if ($sessionId) $query->where('academic_session_id', $sessionId);
        if ($routeId)   $query->whereHas('allocation', fn ($q) => $q->where('transport_route_id', $routeId));

        $payments = $query->orderByDesc('payment_date')->orderByDesc('id')->get();

        $rows = $payments->values()->map(fn ($p, $i) => [
            $i + 1,
            $p->payment_date?->format('d-m-Y') ?? '—',
            $p->student?->name ?? '—',
            $p->student?->roll_no ?? '—',
            $p->allocation?->route?->name ?? '—',
            $p->allocation?->stop?->stop_name ?? '—',
            ucfirst($p->payment_mode),
            $p->reference_no ?? '—',
            number_format((float) $p->amount, 2),
        ]);

        return Excel::download(
            new TransportCollectionExport(collect($rows)),
            'transport-collection-' . $dateFrom . '-to-' . $dateTo . '.xlsx'
        );
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
