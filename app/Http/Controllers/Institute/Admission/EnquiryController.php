<?php

namespace App\Http\Controllers\Institute\Admission;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enquiry;
use App\Models\EnquiryFollowUp;
use App\Models\StaffMember;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    private function instituteId(): int
    {
        return (int) auth()->user()->institute_id;
    }

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();
        $search = (string) $request->input('search');

        $enquiries = Enquiry::forInstitute($instituteId)
            ->with(['course', 'assignedStaff'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->input('course_id')))
            ->when($request->filled('assigned_staff_id'), fn ($q) => $q->where('assigned_staff_id', $request->input('assigned_staff_id')))
            ->when($search !== '', fn ($q) => $q->where(fn ($sq) => $sq
                ->where('name', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('date_to')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $courses = Course::where('institute_id', $instituteId)->orderBy('name')->get();
        $staffMembers = StaffMember::where('institute_id', $instituteId)->orderBy('name')->get();

        $shortName = auth()->user()->institute?->short_name;
        $publicUrl = $shortName ? url('/apply/' . strtolower($shortName)) : null;

        return view('institute.admission.enquiries.index', compact('enquiries', 'courses', 'staffMembers', 'publicUrl'));
    }

    public function show(int $id)
    {
        $instituteId = $this->instituteId();

        $enquiry = Enquiry::forInstitute($instituteId)
            ->with(['course', 'assignedStaff', 'followUps.staff'])
            ->findOrFail($id);

        $staffMembers = StaffMember::where('institute_id', $instituteId)->orderBy('name')->get();

        return view('institute.admission.enquiries.show', compact('enquiry', 'staffMembers'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $enquiry = Enquiry::forInstitute($this->instituteId())->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:new,contacted,interested,not_interested,junk',
        ]);

        $enquiry->update(['status' => $validated['status']]);

        return back()->with('success', 'Enquiry status updated.');
    }

    public function assign(Request $request, int $id)
    {
        $instituteId = $this->instituteId();
        $enquiry = Enquiry::forInstitute($instituteId)->findOrFail($id);

        $validated = $request->validate([
            'assigned_staff_id' => 'nullable|integer|exists:staff_members,id',
        ]);

        if (!empty($validated['assigned_staff_id'])) {
            $belongsToInstitute = StaffMember::where('id', $validated['assigned_staff_id'])
                ->where('institute_id', $instituteId)
                ->exists();
            abort_unless($belongsToInstitute, 422);
        }

        $enquiry->update(['assigned_staff_id' => $validated['assigned_staff_id'] ?? null]);

        return back()->with('success', 'Enquiry assigned.');
    }

    public function storeFollowUp(Request $request, int $id)
    {
        $enquiry = Enquiry::forInstitute($this->instituteId())->findOrFail($id);

        $validated = $request->validate([
            'type'              => 'required|in:call,whatsapp,email,note',
            'note'              => 'required|string|max:1000',
            'next_follow_up_at' => 'nullable|date',
        ]);

        EnquiryFollowUp::create([
            'enquiry_id'        => $enquiry->id,
            'staff_id'          => null,
            'type'              => $validated['type'],
            'note'              => $validated['note'],
            'next_follow_up_at' => $validated['next_follow_up_at'] ?? null,
        ]);

        return back()->with('success', 'Follow-up added.');
    }
}
