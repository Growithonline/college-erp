<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\Library\LibraryRuleSet;
use Illuminate\Http\Request;

class LibraryRuleSetController extends BaseLibraryController
{
    public function index()
    {
        $this->ensureLibraryPermission('manage');
        $records = LibraryRuleSet::forInstitute($this->instituteId())
            ->withCount('members')
            ->orderBy('member_type')
            ->orderBy('name')
            ->get();

        return view('institute.library.masters.index', [
            'pageTitle' => 'Library Rule Sets',
            'pageIcon' => 'bi-shield-check',
            'pageDescription' => 'Student, staff, faculty ke issue limits aur fine rules yahin define honge.',
            'routePrefix' => $this->routeName('rules'),
            'records' => $records,
            'fields' => [
                ['name' => 'name', 'label' => 'Rule Name', 'required' => true, 'placeholder' => 'e.g. Student Default'],
                ['name' => 'member_type', 'label' => 'Member Type', 'type' => 'select', 'required' => true, 'options' => ['student' => 'Student', 'staff' => 'Staff', 'faculty' => 'Faculty']],
                ['name' => 'max_books', 'label' => 'Max Books', 'type' => 'number', 'required' => true],
                ['name' => 'loan_days', 'label' => 'Loan Days', 'type' => 'number', 'required' => true],
                ['name' => 'fine_per_day', 'label' => 'Fine / Day', 'type' => 'number', 'required' => true, 'step' => '0.01'],
                ['name' => 'grace_days', 'label' => 'Grace Days', 'type' => 'number', 'required' => true],
                ['name' => 'max_renewals', 'label' => 'Max Renewals', 'type' => 'number', 'required' => true],
                ['name' => 'allow_reservation', 'label' => 'Allow Reservation', 'type' => 'checkbox'],
            ],
            'columns' => [
                ['label' => 'Rule', 'value' => fn($record) => $record->name . ' (' . ucfirst($record->member_type) . ')'],
                ['label' => 'Borrowing', 'value' => fn($record) => $record->max_books . ' books / ' . $record->loan_days . ' days'],
                ['label' => 'Fine', 'value' => fn($record) => 'Rs ' . number_format((float) $record->fine_per_day, 2) . ' | Grace ' . $record->grace_days . 'd'],
                ['label' => 'Members', 'value' => fn($record) => $record->members_count],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureLibraryPermission('manage');
        $data = $this->validatedData($request);
        $data['institute_id'] = $this->instituteId();
        $data['is_active'] = true;

        LibraryRuleSet::create($data);

        return back()->with('success', 'Rule set add ho gaya.');
    }

    public function update(Request $request, LibraryRuleSet $rule)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($rule->institute_id !== $this->instituteId(), 403);
        $rule->update($this->validatedData($request));

        return back()->with('success', 'Rule set update ho gaya.');
    }

    public function toggle(LibraryRuleSet $rule)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($rule->institute_id !== $this->instituteId(), 403);
        $rule->update(['is_active' => !$rule->is_active]);

        return back()->with('success', 'Rule set status update ho gaya.');
    }

    public function destroy(LibraryRuleSet $rule)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($rule->institute_id !== $this->instituteId(), 403);

        if ($rule->members()->exists()) {
            return back()->withErrors(['delete' => 'Is rule set se members linked hain.']);
        }

        $rule->delete();

        return back()->with('success', 'Rule set delete ho gaya.');
    }

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'member_type' => 'required|in:student,staff,faculty',
            'max_books' => 'required|integer|min:1|max:50',
            'loan_days' => 'required|integer|min:1|max:365',
            'fine_per_day' => 'required|numeric|min:0|max:99999',
            'grace_days' => 'required|integer|min:0|max:60',
            'max_renewals' => 'required|integer|min:0|max:20',
        ]);

        $validated['allow_reservation'] = $request->boolean('allow_reservation');

        return $validated;
    }
}
