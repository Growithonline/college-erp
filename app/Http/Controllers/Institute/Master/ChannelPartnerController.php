<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Mail\PartnerCredentialsMail;
use App\Models\AcademicSession;
use App\Models\ChannelPartner;
use App\Models\Course;
use App\Models\CourseType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\InstituteMailer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class ChannelPartnerController extends Controller
{
    private const PAY_MODES = ['cash', 'upi', 'online', 'cheque', 'dd', 'neft', 'rtgs'];

    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    private function formData(): array
    {
        $id = $this->instituteId();
        return [
            'courseTypes' => CourseType::forInstitute($id)->active()->orderBy('sort_order')->orderBy('name')->get(),
            'courses'  => Course::where('institute_id', $id)->where('status', true)
                            ->orderBy('name')->get(['id', 'name', 'course_type_id']),
            'sessions' => AcademicSession::where('institute_id', $id)
                            ->orderByDesc('is_active')->orderByDesc('id')->get(['id', 'name', 'is_active']),
            'payModes' => self::PAY_MODES,
        ];
    }

    public function index()
    {
        $id = $this->instituteId();
        $partners = ChannelPartner::where('institute_id', $id)->orderBy('name')->get();
        $trashedCount = ChannelPartner::onlyTrashed()->where('institute_id', $id)->count();
        return view('institute.master.channel-partners.index', compact('partners', 'trashedCount'));
    }

    public function create()
    {
        return view('institute.master.channel-partners.create', $this->formData());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:100',
            'mobile'              => 'required|digits:10',
            'email'               => ['required', 'email', Rule::unique('channel_partners', 'email')->whereNull('deleted_at')],
            'address'             => 'nullable|string|max:255',
            'city'                => 'nullable|string|max:50',
            'state'               => 'nullable|string|max:50',
            'commission_percent'  => 'nullable|numeric|min:0|max:100',
            'admission_form_type' => 'required|in:full,quick,both',
            'allowed_courses'     => 'nullable|array',
            'allowed_courses.*'   => 'integer|exists:courses,id',
            'student_scope'       => 'required|in:own,all',
            'fee_scope'           => 'required|in:own,all',
            'max_discount_pct'    => 'nullable|numeric|min:0|max:100',
        ]);

        $plainPassword = Str::random(8);

        $partner = ChannelPartner::create([
            'institute_id'         => $this->instituteId(),
            'name'                 => strtoupper($request->name),
            'mobile'               => $request->mobile,
            'email'                => $request->email,
            'password'             => Hash::make($plainPassword),
            'address'              => $request->address,
            'city'                 => $request->city,
            'state'                => $request->state,
            'commission_percent'   => $request->commission_percent ?? 0,
            'status'               => true,
            // Feature flags
            'can_add_admission'    => $request->boolean('can_add_admission', true),
            'can_view_students'    => $request->boolean('can_view_students', true),
            'can_collect_fee'      => $request->boolean('can_collect_fee', false),
            // Admission controls
            'admission_form_type'  => $request->admission_form_type,
            'allowed_courses'      => $request->filled('allowed_courses') ? $request->allowed_courses : null,
            'allowed_sessions'     => $this->parseSessionPerms($request),
            // Student & fee scope
            'student_scope'        => $request->student_scope,
            'fee_scope'            => $request->fee_scope,
            // Discount
            'can_give_discount'    => $request->boolean('can_give_discount'),
            'max_discount_pct'     => $request->boolean('can_give_discount') ? ($request->max_discount_pct ?? 0) : 0,
            'can_waive_fee'        => $request->boolean('can_waive_fee'),
            // Reports
            'can_download_reports' => $request->boolean('can_download_reports'),
        ]);

        $credentialsSent = $this->sendCredentialsEmail($partner, $plainPassword);

        $message = "Partner '{$request->name}' created successfully!";
        if ($credentialsSent) {
            $message .= " Login credentials sent to {$request->email}.";
        } else {
            $message .= " Email could not be sent. Share manually — Email: {$request->email} | Password: {$plainPassword}";
        }

        return redirect()->route('master.channel-partners.index')->with('success', $message);
    }

    public function edit(ChannelPartner $channelPartner)
    {
        abort_if($channelPartner->institute_id !== $this->instituteId(), 403);
        return view('institute.master.channel-partners.edit', array_merge(
            compact('channelPartner'),
            $this->formData()
        ));
    }

    public function update(Request $request, ChannelPartner $channelPartner)
    {
        abort_if($channelPartner->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'name'                => 'required|string|max:100',
            'mobile'              => 'required|digits:10',
            'commission_percent'  => 'nullable|numeric|min:0|max:100',
            'admission_form_type' => 'required|in:full,quick,both',
            'allowed_courses'     => 'nullable|array',
            'allowed_courses.*'   => 'integer|exists:courses,id',
            'student_scope'       => 'required|in:own,all',
            'fee_scope'           => 'required|in:own,all',
            'max_discount_pct'    => 'nullable|numeric|min:0|max:100',
        ]);

        $data = [
            'name'                 => strtoupper($request->name),
            'mobile'               => $request->mobile,
            'address'              => $request->address,
            'city'                 => $request->city,
            'state'                => $request->state,
            'commission_percent'   => $request->commission_percent ?? 0,
            // Feature flags
            'can_add_admission'    => $request->boolean('can_add_admission'),
            'can_view_students'    => $request->boolean('can_view_students'),
            'can_collect_fee'      => $request->boolean('can_collect_fee'),
            // Admission controls
            'admission_form_type'  => $request->admission_form_type,
            'allowed_courses'      => $request->filled('allowed_courses') ? $request->allowed_courses : null,
            'allowed_sessions'     => $this->parseSessionPerms($request),
            // Student & fee scope
            'student_scope'        => $request->student_scope,
            'fee_scope'            => $request->fee_scope,
            // Discount
            'can_give_discount'    => $request->boolean('can_give_discount'),
            'max_discount_pct'     => $request->boolean('can_give_discount') ? ($request->max_discount_pct ?? 0) : 0,
            'can_waive_fee'        => $request->boolean('can_waive_fee'),
            // Reports
            'can_download_reports' => $request->boolean('can_download_reports'),
        ];

        $newPassword = null;
        if ($request->boolean('reset_password')) {
            $newPassword      = Str::random(8);
            $data['password'] = Hash::make($newPassword);
        }

        $channelPartner->update($data);

        $msg = 'Partner updated successfully!';
        if ($newPassword) {
            $sent = $this->sendCredentialsEmail($channelPartner->fresh(), $newPassword);
            $msg .= $sent
                ? ' New password sent to the registered email.'
                : " New Password: {$newPassword}";
        }

        return redirect()->route('master.channel-partners.index')->with('success', $msg);
    }

    public function destroy(ChannelPartner $channelPartner)
    {
        abort_if($channelPartner->institute_id !== $this->instituteId(), 403);
        try {
            $channelPartner->delete();
            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => "Partner \"{$channelPartner->name}\" archived successfully."]);
            }
            return redirect()->route('master.channel-partners.index')->with('success', "Partner \"{$channelPartner->name}\" archived. You can restore from the Archived list.");
        } catch (Throwable $e) {
            $msg = 'Could not archive this partner. Please try again.';
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }
    }

    public function trashed()
    {
        $partners = ChannelPartner::onlyTrashed()
            ->where('institute_id', $this->instituteId())
            ->orderByDesc('deleted_at')
            ->get();

        return view('institute.master.channel-partners.trashed', compact('partners'));
    }

    public function restore(int $id)
    {
        $partner = ChannelPartner::onlyTrashed()
            ->where('institute_id', $this->instituteId())
            ->findOrFail($id);

        $partner->restore();

        return redirect()->route('master.channel-partners.trashed')
            ->with('success', "Partner \"{$partner->name}\" restored successfully.");
    }

    public function forceDelete(int $id)
    {
        $partner = ChannelPartner::onlyTrashed()
            ->where('institute_id', $this->instituteId())
            ->findOrFail($id);

        $partner->forceDelete();

        return redirect()->route('master.channel-partners.trashed')
            ->with('success', "Partner \"{$partner->name}\" permanently deleted.");
    }

    public function toggle(ChannelPartner $channelPartner)
    {
        abort_if($channelPartner->institute_id !== $this->instituteId(), 403);
        $channelPartner->update(['status' => !$channelPartner->status]);
        return back()->with('success', 'Status updated!');
    }

    private function parseSessionPerms(Request $request): ?array
    {
        $input = $request->input('session_perms', []);
        if (empty($input)) return null;

        $result = [];
        foreach ($input as $sessionId => $perms) {
            if (!empty($perms['enabled'])) {
                $result[] = [
                    'id'            => (int) $sessionId,
                    'admission'     => !empty($perms['admission']),
                    'fee'           => !empty($perms['fee']),
                    'view'          => !empty($perms['view']),
                    'student_scope' => in_array($perms['student_scope'] ?? '', ['all']) ? 'all' : 'own',
                    'fee_scope'     => in_array($perms['fee_scope'] ?? '', ['all']) ? 'all' : 'own',
                ];
            }
        }

        return empty($result) ? null : $result;
    }

    private function sendCredentialsEmail(ChannelPartner $partner, string $plainPassword): bool
    {
        if (!$partner->email) {
            return false;
        }

        try {
            $partner->loadMissing('institute');
            InstituteMailer::send($partner->institute_id, $partner->email, new PartnerCredentialsMail($partner, $plainPassword));
            return true;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }
}
