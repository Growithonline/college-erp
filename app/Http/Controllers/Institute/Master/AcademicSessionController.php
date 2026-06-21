<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use Illuminate\Http\Request;

class AcademicSessionController extends Controller
{
    public function index()
    {
        $sessions = AcademicSession::where('institute_id', auth()->user()->institute_id)
            ->orderByDesc('start_date')
            ->get();

        return view('institute.master.sessions.index', compact('sessions'));
    }

    public function create()
    {
        return view('institute.master.sessions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:20',
            'academic_year' => 'nullable|string|max:10|regex:/^\d{4}-\d{2,4}$/',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
        ]);

        $instituteId = auth()->user()->institute_id;

        // Duplicate check
        $exists = AcademicSession::where('institute_id', $instituteId)
            ->where('name', $request->name)->exists();

        if ($exists) {
            return back()->withInput()
                ->withErrors(['name' => 'This session already exists.']);
        }

        $isFirst = AcademicSession::where('institute_id', $instituteId)->count() === 0;

        $session = AcademicSession::create([
            'institute_id'  => $instituteId,
            'name'          => $request->name,
            'academic_year' => $request->academic_year ?: null,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'is_active'     => $isFirst,
        ]);

        return redirect()->route('master.sessions.index')
            ->with('success', "Session '{$session->name}' created successfully!" . ($isFirst ? ' Auto-activated as first session.' : ''));
    }

    public function edit(AcademicSession $session)
    {
        $this->authorizeSession($session);
        return view('institute.master.sessions.edit', compact('session'));
    }

    public function update(Request $request, AcademicSession $session)
    {
        $this->authorizeSession($session);

        $request->validate([
            'name'          => 'required|string|max:20',
            'academic_year' => 'nullable|string|max:10|regex:/^\d{4}-\d{2,4}$/',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
        ]);

        $session->update([
            'name'          => $request->name,
            'academic_year' => $request->academic_year ?: null,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
        ]);

        return redirect()->route('master.sessions.index')
            ->with('success', "Session updated successfully!");
    }

    public function destroy(AcademicSession $session)
    {
        $this->authorizeSession($session);

        if ($session->is_active) {
            return back()->with('error', 'Cannot delete an active session. Activate another session first.');
        }

        $session->delete();
        return redirect()->route('master.sessions.index')
            ->with('success', 'Session deleted successfully!');
    }

    public function activate(AcademicSession $session)
    {
        $this->authorizeSession($session);
        $session->activate();

        return redirect()->route('master.sessions.index')
            ->with('success', "Session '{$session->name}' activated!");
    }

    private function authorizeSession(AcademicSession $session): void
    {
        abort_if($session->institute_id !== auth()->user()->institute_id, 403);
    }
}
