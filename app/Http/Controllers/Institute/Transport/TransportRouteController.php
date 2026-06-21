<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\TransportRoute;
use App\Models\TransportRouteStop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransportRouteController extends TransportBaseController
{
    public function index()
    {
        $routes = TransportRoute::withCount(['stops', 'allocations'])
            ->where('institute_id', $this->instituteId())
            ->orderBy('name')
            ->paginate(20);

        return view('institute.transport.routes.index', compact('routes'));
    }

    public function create()
    {
        return view('institute.transport.routes.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateRoute($request);

        DB::transaction(function () use ($data, $request) {
            $routeData = [
                'institute_id' => $this->instituteId(),
                'route_code'   => strtoupper(trim($data['route_code'])),
                'name'         => trim($data['name']),
                'start_point'  => $data['start_point'] ?? null,
                'end_point'    => $data['end_point'] ?? null,
                'distance_km'  => $data['distance_km'] ?? null,
                'fee_amount'   => $data['fee_amount'] ?? 0,
                'morning_time' => $data['morning_time'] ?? null,
                'evening_time' => $data['evening_time'] ?? null,
                'notes'        => $data['notes'] ?? null,
                'status'       => $request->boolean('status', true),
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('transport_routes', 'billing_frequency')) {
                $routeData['billing_frequency'] = $data['billing_frequency'] ?? 'one_time';
            }
            $route = TransportRoute::create($routeData);

            $this->syncStops($route, $data['stops'] ?? []);
        });

        return redirect()->route('transport.routes.index')->with('success', 'Route created successfully.');
    }

    public function show(TransportRoute $route)
    {
        $this->assertInstituteModel($route);
        $route->load([
            'stops' => fn ($query) => $query->orderBy('sequence'),
            'allocations' => fn ($query) => $query->where('is_active', true)->with(['student', 'vehicle', 'driver']),
        ]);

        return view('institute.transport.routes.show', compact('route'));
    }

    public function edit(TransportRoute $route)
    {
        $this->assertInstituteModel($route);
        $route->load('stops');

        return view('institute.transport.routes.edit', compact('route'));
    }

    public function update(Request $request, TransportRoute $route)
    {
        $this->assertInstituteModel($route);
        $data = $this->validateRoute($request, $route->id);

        DB::transaction(function () use ($data, $request, $route) {
            $updateData = [
                'route_code'   => strtoupper(trim($data['route_code'])),
                'name'         => trim($data['name']),
                'start_point'  => $data['start_point'] ?? null,
                'end_point'    => $data['end_point'] ?? null,
                'distance_km'  => $data['distance_km'] ?? null,
                'fee_amount'   => $data['fee_amount'] ?? 0,
                'morning_time' => $data['morning_time'] ?? null,
                'evening_time' => $data['evening_time'] ?? null,
                'notes'        => $data['notes'] ?? null,
                'status'       => $request->boolean('status', true),
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('transport_routes', 'billing_frequency')) {
                $updateData['billing_frequency'] = $data['billing_frequency'] ?? 'one_time';
            }
            $route->update($updateData);

            $this->syncStops($route, $data['stops'] ?? []);
        });

        return redirect()->route('transport.routes.index')->with('success', 'Route updated successfully.');
    }

    public function destroy(TransportRoute $route)
    {
        $this->assertInstituteModel($route);
        abort_if($route->allocations()->exists(), 422, 'Route cannot be deleted because allocations exist.');

        $route->delete();

        return back()->with('success', 'Route deleted successfully.');
    }

    public function toggle(TransportRoute $route)
    {
        $this->assertInstituteModel($route);
        $route->update(['status' => !$route->status]);

        return back()->with('success', 'Route status updated.');
    }

    public function stops(TransportRoute $route): JsonResponse
    {
        $this->assertInstituteModel($route);

        $stops = $route->stops()
            ->where('status', true)
            ->orderBy('sequence')
            ->get(['id', 'stop_name', 'landmark', 'sequence', 'fee_amount'])
            ->map(fn (TransportRouteStop $stop) => [
                'id'         => $stop->id,
                'stop_name'  => $stop->stop_name,
                'landmark'   => $stop->landmark,
                'sequence'   => $stop->sequence,
                'fee_amount' => (float) $stop->fee_amount,
            ])
            ->values();

        return response()->json(['stops' => $stops]);
    }

    private function validateRoute(Request $request, ?int $routeId = null): array
    {
        $instituteId = $this->instituteId();

        return $request->validate([
            'route_code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('transport_routes', 'route_code')
                    ->where(fn ($query) => $query->where('institute_id', $instituteId))
                    ->ignore($routeId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'start_point' => ['nullable', 'string', 'max:180'],
            'end_point' => ['nullable', 'string', 'max:180'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'fee_amount'        => ['nullable', 'numeric', 'min:0'],
            'billing_frequency' => ['nullable', 'in:one_time,monthly,quarterly,semester,yearly'],
            'morning_time' => ['nullable', 'date_format:H:i'],
            'evening_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
            'stops' => ['nullable', 'array'],
            'stops.*.stop_name' => ['nullable', 'string', 'max:160'],
            'stops.*.landmark' => ['nullable', 'string', 'max:180'],
            'stops.*.sequence' => ['nullable', 'integer', 'min:1'],
            'stops.*.fee_amount' => ['nullable', 'numeric', 'min:0'],
            'stops.*.pickup_time' => ['nullable', 'date_format:H:i'],
            'stops.*.drop_time' => ['nullable', 'date_format:H:i'],
        ]);
    }

    private function syncStops(TransportRoute $route, array $stops): void
    {
        $stops = collect($stops)
            ->filter(fn ($stop) => trim((string) ($stop['stop_name'] ?? '')) !== '')
            ->values();

        if ($stops->isEmpty()) {
            return;
        }

        if (!$route->allocations()->exists()) {
            $route->stops()->delete();
        }

        foreach ($stops as $index => $stopData) {
            $payload = [
                'stop_name'  => trim((string) $stopData['stop_name']),
                'landmark'   => $stopData['landmark'] ?? null,
                'sequence'   => (int) ($stopData['sequence'] ?? ($index + 1)),
                'fee_amount' => (float) ($stopData['fee_amount'] ?? 0),
                'pickup_time' => $stopData['pickup_time'] ?? null,
                'drop_time'  => $stopData['drop_time'] ?? null,
                'status'     => true,
            ];

            if ($route->allocations()->exists()) {
                TransportRouteStop::updateOrCreate(
                    [
                        'transport_route_id' => $route->id,
                        'sequence' => $payload['sequence'],
                    ],
                    $payload
                );
            } else {
                $route->stops()->create($payload);
            }
        }
    }
}
