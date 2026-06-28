<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Jobs\SendNoticeSmsJob;
use App\Mail\NoticePublishedMail;
use App\Models\Center;
use App\Models\ChannelPartner;
use App\Models\Notice;
use App\Models\NoticeRead;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\InstituteMailer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class NoticeController extends Controller
{
    private function instituteId(): int
    {
        foreach (['web', 'staff'] as $guard) {
            $user = auth()->guard($guard)->user();
            if ($user && $user->institute_id) {
                return (int) $user->institute_id;
            }
        }
        abort(403, 'Not authenticated');
    }

    private function staffLayout(): array
    {
        if (auth()->guard('staff')->check()) {
            return ['layout' => 'staff.layout', 'rp' => 'staff.notices'];
        }
        return [];
    }

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();

        $notices = Notice::where('institute_id', $instituteId)
            ->when($request->type, fn($q) => $q->where('notice_type', $request->type))
            ->when($request->filled('active_only'), fn($q) => $q->active())
            ->withCount('reads')
            ->orderByDesc('is_pinned')
            ->orderByDesc('notice_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('institute.notices.index', compact('notices'))
            ->with($this->staffLayout());
    }

    public function create()
    {
        $types     = Notice::TYPES;
        $visibleTo = Notice::VISIBLE_TO;

        return view('institute.notices.create', compact('types', 'visibleTo'))
            ->with($this->staffLayout());
    }

    public function store(Request $request)
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'body'         => 'required|string|max:10000',
            'notice_type'   => 'required|in:' . implode(',', array_keys(Notice::TYPES)),
            'visible_to'    => 'required|array|min:1',
            'visible_to.*'  => 'in:' . implode(',', array_keys(Notice::VISIBLE_TO)),
            'notice_date'   => 'required|date',
            'expires_at'    => 'nullable|date|after_or_equal:notice_date',
            'scheduled_at'  => 'nullable|date',
            'is_pinned'     => 'boolean',
            'attachment'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'email_roles'   => 'nullable|array',
            'email_roles.*' => 'in:staff,center,channel,student',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('notices', 'public');
        }

        // email_to: null = no email, string = roles to email (e.g. "staff,center,student")
        $emailTo = null;
        if ($request->boolean('send_email') && !empty($validated['email_roles'])) {
            $emailTo = implode(',', $validated['email_roles']);
        }

        $smsTo = null;
        if ($request->boolean('send_sms') && $request->filled('sms_roles')) {
            $smsTo = implode(',', array_filter((array) $request->sms_roles));
        }

        $staffId = auth()->guard('staff')->id();
        $userId  = auth()->guard('web')->id();

        $notice = Notice::create([
            'institute_id'       => $instituteId,
            'title'              => $validated['title'],
            'body'               => $validated['body'],
            'notice_type'        => $validated['notice_type'],
            'visible_to'         => $validated['visible_to'],
            'notice_date'        => $validated['notice_date'],
            'expires_at'         => $validated['expires_at'] ?? null,
            'scheduled_at'       => $validated['scheduled_at'] ?? null,
            'is_active'          => true,
            'is_pinned'          => $request->boolean('is_pinned'),
            'attachment'         => $attachmentPath,
            'email_to'           => $emailTo,
            'sms_to'             => $smsTo,
            'posted_by_staff_id' => $staffId,
            'posted_by_user_id'  => $userId,
        ]);

        // Email sirf tab bhejo jab admin ne explicitly enable kiya ho aur schedule nahi hai
        if ($emailTo && (!$notice->scheduled_at || $notice->scheduled_at->lte(now()))) {
            $this->dispatchEmails($notice);
        }

        // SMS broadcast queue mein daalo
        if ($smsTo && (!$notice->scheduled_at || $notice->scheduled_at->lte(now()))) {
            SendNoticeSmsJob::dispatch($notice->id);
        }

        $rp = auth()->guard('staff')->check() ? 'staff.notices' : 'notices';
        return redirect()->route("{$rp}.index")->with('success', 'Notice post ho gaya!');
    }

    public function edit(Notice $notice)
    {
        abort_if($notice->institute_id !== $this->instituteId(), 403);

        $types     = Notice::TYPES;
        $visibleTo = Notice::VISIBLE_TO;

        return view('institute.notices.edit', compact('notice', 'types', 'visibleTo'))
            ->with($this->staffLayout());
    }

    public function update(Request $request, Notice $notice)
    {
        abort_if($notice->institute_id !== $this->instituteId(), 403);

        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'body'         => 'required|string|max:10000',
            'notice_type'   => 'required|in:' . implode(',', array_keys(Notice::TYPES)),
            'visible_to'    => 'required|array|min:1',
            'visible_to.*'  => 'in:' . implode(',', array_keys(Notice::VISIBLE_TO)),
            'notice_date'   => 'required|date',
            'expires_at'    => 'nullable|date|after_or_equal:notice_date',
            'scheduled_at'  => 'nullable|date',
            'is_active'     => 'boolean',
            'is_pinned'     => 'boolean',
            'attachment'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'email_roles'   => 'nullable|array',
            'email_roles.*' => 'in:staff,center,channel,student',
        ]);

        if ($request->hasFile('attachment')) {
            if ($notice->attachment) {
                Storage::disk('public')->delete($notice->attachment);
            }
            $validated['attachment'] = $request->file('attachment')->store('notices', 'public');
        }

        $emailTo = null;
        if ($request->boolean('send_email') && !empty($validated['email_roles'])) {
            $emailTo = implode(',', $validated['email_roles']);
        }

        $smsTo = null;
        if ($request->boolean('send_sms') && $request->filled('sms_roles')) {
            $smsTo = implode(',', array_filter((array) $request->sms_roles));
        }

        $notice->update([
            'title'        => $validated['title'],
            'body'         => $validated['body'],
            'notice_type'  => $validated['notice_type'],
            'visible_to'   => $validated['visible_to'],
            'notice_date'  => $validated['notice_date'],
            'expires_at'   => $validated['expires_at'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'is_active'    => $request->boolean('is_active', true),
            'is_pinned'    => $request->boolean('is_pinned'),
            'attachment'   => $validated['attachment'] ?? $notice->attachment,
            'email_to'     => $emailTo,
            'sms_to'       => $smsTo,
        ]);

        $rp = auth()->guard('staff')->check() ? 'staff.notices' : 'notices';
        return redirect()->route("{$rp}.index")->with('success', 'Notice update ho gaya!');
    }

    public function destroy(Notice $notice)
    {
        abort_if($notice->institute_id !== $this->instituteId(), 403);

        if ($notice->attachment) {
            Storage::disk('public')->delete($notice->attachment);
        }

        $notice->reads()->delete();
        $notice->delete();

        $rp = auth()->guard('staff')->check() ? 'staff.notices' : 'notices';
        return redirect()->route("{$rp}.index")->with('success', 'Notice delete ho gaya!');
    }

    public function toggle(Notice $notice)
    {
        abort_if($notice->institute_id !== $this->instituteId(), 403);

        $notice->update(['is_active' => !$notice->is_active]);

        $rp = auth()->guard('staff')->check() ? 'staff.notices' : 'notices';
        return redirect()->route("{$rp}.index")
            ->with('success', 'Notice ' . ($notice->is_active ? 'activate' : 'deactivate') . ' ho gaya!');
    }

    public function pin(Notice $notice)
    {
        abort_if($notice->institute_id !== $this->instituteId(), 403);

        $notice->update(['is_pinned' => !$notice->is_pinned]);

        $rp = auth()->guard('staff')->check() ? 'staff.notices' : 'notices';
        return redirect()->route("{$rp}.index")
            ->with('success', 'Notice ' . ($notice->is_pinned ? 'pin' : 'unpin') . ' ho gaya!');
    }

    public function markRead(Request $request, Notice $notice)
    {
        $type = $request->input('reader_type');
        $id   = $request->input('reader_id');

        if ($type && $id) {
            NoticeRead::firstOrCreate([
                'notice_id'   => $notice->id,
                'reader_type' => $type,
                'reader_id'   => $id,
            ], ['read_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    public function readDetail(Notice $notice)
    {
        abort_if($notice->institute_id !== $this->instituteId(), 403);

        $reads = $notice->reads()->orderByDesc('read_at')->get();

        $typeMap = [
            'staff'   => \App\Models\StaffMember::class,
            'center'  => \App\Models\Center::class,
            'partner' => \App\Models\ChannelPartner::class,
            'student' => \App\Models\Student::class,
        ];

        $rows = $reads->map(function ($r) use ($typeMap) {
            $name = '—';
            if (isset($typeMap[$r->reader_type])) {
                $model = $typeMap[$r->reader_type]::find($r->reader_id);
                $name  = $model?->name ?? "#{$r->reader_id}";
            }
            return [
                'type'    => ucfirst($r->reader_type),
                'name'    => $name,
                'read_at' => $r->read_at?->format('d M Y, h:i A'),
            ];
        });

        return response()->json($rows);
    }

    private function dispatchEmails(Notice $notice): void
    {
        if (!$notice->email_to) return;

        $instituteId = $notice->institute_id;
        $roles       = explode(',', $notice->email_to); // ['staff','center','channel','student']
        $recipients  = collect();

        if (in_array('staff', $roles)) {
            StaffMember::where('institute_id', $instituteId)
                ->where('status', true)
                ->whereNotNull('email')
                ->pluck('email')
                ->each(fn($e) => $recipients->push($e));
        }

        if (in_array('center', $roles)) {
            Center::where('institute_id', $instituteId)
                ->where('status', true)
                ->whereNotNull('email')
                ->pluck('email')
                ->each(fn($e) => $recipients->push($e));
        }

        if (in_array('channel', $roles) && class_exists(ChannelPartner::class)) {
            ChannelPartner::where('institute_id', $instituteId)
                ->where('status', true)
                ->whereNotNull('email')
                ->pluck('email')
                ->each(fn($e) => $recipients->push($e));
        }

        if (in_array('student', $roles)) {
            Student::where('institute_id', $instituteId)
                ->where('status', 'active')
                ->whereNotNull('email')
                ->pluck('email')
                ->each(fn($e) => $recipients->push($e));
        }

        foreach ($recipients->unique() as $email) {
            try {
                InstituteMailer::queue($instituteId, $email, new NoticePublishedMail($notice));
            } catch (\Throwable) {
                // silent fail — notice create block na ho
            }
        }
    }
}
