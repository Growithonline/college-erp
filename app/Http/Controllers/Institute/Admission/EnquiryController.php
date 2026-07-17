<?php

namespace App\Http\Controllers\Institute\Admission;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationLinkMail;
use App\Models\Course;
use App\Models\Enquiry;
use App\Models\EnquiryFollowUp;
use App\Models\Institute;
use App\Models\StaffMember;
use App\Services\InstituteMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EnquiryController extends Controller
{
    private function actorGuard(): string
    {
        return auth()->guard('staff')->check() ? 'staff' : 'web';
    }

    private function actorUser()
    {
        return auth()->guard($this->actorGuard())->user();
    }

    private function instituteId(): int
    {
        return (int) $this->actorUser()->institute_id;
    }

    private function routePrefix(): string
    {
        return $this->actorGuard() === 'staff' ? 'staff.' : '';
    }

    private function viewLayout(): string
    {
        return $this->actorGuard() === 'staff' ? 'staff.layout' : 'institute.layout';
    }

    private function currentStaffId(): ?int
    {
        return $this->actorGuard() === 'staff' ? $this->actorUser()->id : null;
    }

    private function canViewAllEnquiries(): bool
    {
        return $this->actorGuard() !== 'staff' || $this->actorUser()->hasPermission('enquiry_view_all');
    }

    private function ensureCanAccess(Enquiry $enquiry): void
    {
        if ($this->canViewAllEnquiries()) {
            return;
        }
        abort_unless($enquiry->assigned_staff_id === $this->currentStaffId(), 403, 'This enquiry is not assigned to you.');
    }

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();
        $search = (string) $request->input('search');
        $canViewAll = $this->canViewAllEnquiries();

        $enquiries = Enquiry::forInstitute($instituteId)
            ->with(['course', 'assignedStaff'])
            ->when(!$canViewAll, fn ($q) => $q->where('assigned_staff_id', $this->currentStaffId()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->input('course_id')))
            ->when($canViewAll && $request->filled('assigned_staff_id'), fn ($q) => $q->where('assigned_staff_id', $request->input('assigned_staff_id')))
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

        $shortName = Institute::find($instituteId)?->short_name;
        $publicUrl = $shortName ? url('/apply/' . strtolower($shortName)) : null;

        return view('institute.admission.enquiries.index', [
            'enquiries'   => $enquiries,
            'courses'     => $courses,
            'staffMembers' => $staffMembers,
            'publicUrl'   => $publicUrl,
            'layout'      => $this->viewLayout(),
            'routePrefix' => $this->routePrefix(),
            'canViewAll'  => $canViewAll,
        ]);
    }

    public function show(int $id)
    {
        $instituteId = $this->instituteId();

        $enquiry = Enquiry::forInstitute($instituteId)
            ->with(['course', 'assignedStaff', 'followUps.staff'])
            ->findOrFail($id);

        $this->ensureCanAccess($enquiry);

        $staffMembers = StaffMember::where('institute_id', $instituteId)->orderBy('name')->get();

        return view('institute.admission.enquiries.show', [
            'enquiry'      => $enquiry,
            'staffMembers' => $staffMembers,
            'layout'       => $this->viewLayout(),
            'routePrefix'  => $this->routePrefix(),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $enquiry = Enquiry::forInstitute($this->instituteId())->findOrFail($id);
        $this->ensureCanAccess($enquiry);

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
        $this->ensureCanAccess($enquiry);

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

    public function sendApplicationLink(int $id)
    {
        $enquiry = Enquiry::forInstitute($this->instituteId())->findOrFail($id);
        $this->ensureCanAccess($enquiry);
        abort_if($enquiry->converted_student_id, 422, 'This enquiry has already been converted to an application.');

        $shortName = Institute::find($this->instituteId())?->short_name;
        abort_unless($shortName, 422, 'Institute short code is not set. Contact support before sending application links.');

        $url = URL::temporarySignedRoute(
            'public.application.show',
            now()->addDays(30),
            ['shortName' => strtolower($shortName), 'enquiry' => $enquiry->id]
        );

        InstituteMailer::send($this->instituteId(), $enquiry->email, new ApplicationLinkMail($enquiry, $url));

        return back()->with('success', 'Application link sent to ' . $enquiry->email . '.');
    }

    public function storeFollowUp(Request $request, int $id)
    {
        $enquiry = Enquiry::forInstitute($this->instituteId())->findOrFail($id);
        $this->ensureCanAccess($enquiry);

        $validated = $request->validate([
            'type'              => 'required|in:call,whatsapp,email,note',
            'note'              => 'required|string|max:1000',
            'next_follow_up_at' => 'nullable|date',
        ]);

        EnquiryFollowUp::create([
            'enquiry_id'        => $enquiry->id,
            'staff_id'          => $this->currentStaffId(),
            'type'              => $validated['type'],
            'note'              => $validated['note'],
            'next_follow_up_at' => $validated['next_follow_up_at'] ?? null,
        ]);

        return back()->with('success', 'Follow-up added.');
    }
}
