<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsBroadcastJob;
use App\Models\Course;
use App\Models\CourseStream;
use App\Models\CourseType;
use App\Models\Notice;
use App\Models\SmsBroadcast;
use App\Models\SmsTemplate;
use App\Models\StaffRole;
use App\Services\SmsBroadcastTargeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsBroadcastController extends Controller
{
    // Template types this compose page can send. OTP, Fee Due Reminder, and Fee Transaction
    // Alert are deliberately excluded — they're auto-triggered by their own dedicated flows
    // (login, Due Reminders page, fee collection), not admin-composed bulk sends.
    private const BROADCASTABLE_TYPES = [
        SmsTemplate::TYPE_NOTICE          => 'Notice',
        SmsTemplate::TYPE_ADMIT_CARD      => 'Admit Card',
        SmsTemplate::TYPE_EXAM_INFO       => 'Exam Info',
        SmsTemplate::TYPE_PROMOTION       => 'Promotion',
        SmsTemplate::TYPE_ADMISSION_ALERT => 'Admission / Credentials',
    ];

    private function instituteId(): int
    {
        return Auth::user()->institute_id;
    }

    public function index()
    {
        $broadcasts = SmsBroadcast::with('smsTemplate')
            ->where('institute_id', $this->instituteId())
            ->orderByDesc('id')
            ->paginate(20);

        return view('institute.master.sms.broadcasts.index', compact('broadcasts'));
    }

    public function create()
    {
        $instituteId = $this->instituteId();

        $templates = SmsTemplate::where('institute_id', $instituteId)
            ->whereIn('type', array_keys(self::BROADCASTABLE_TYPES))
            ->where('is_active', true)
            ->orderBy('type')->orderBy('name')
            ->get();

        $courses    = Course::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $streams    = CourseStream::with('course')->whereIn('course_id', $courses->pluck('id'))->where('status', true)->orderBy('name')->get();
        $staffRoles = StaffRole::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $courseTypes = CourseType::where('institute_id', $instituteId)->where('is_active', true)->orderBy('sort_order')->get();
        $typeLabels = self::BROADCASTABLE_TYPES;

        // Shaped here (not inline in the blade's @json calls) — keeps the view a plain template.
        $templatesForJs = $templates->map(fn (SmsTemplate $t) => [
            'id'      => $t->id,
            'content' => $t->content,
            'vars'    => array_values(array_unique($t->variable_names_array)),
        ])->values();
        $autoVarsStudent  = SmsBroadcastTargeting::recipientAutoVarNames(SmsBroadcast::AUDIENCE_STUDENT);
        $autoVarsStaff    = SmsBroadcastTargeting::recipientAutoVarNames(SmsBroadcast::AUDIENCE_STAFF);
        $overridableVars  = SmsBroadcastTargeting::overridableAutoVarNames();

        // Total semester count per course (years × semesters-per-year) — drives the compose
        // page's Sem checkboxes so a trimester 4-year course (12 sems) isn't capped at 8 the
        // way a plain 4-year semester course would be.
        $courseSemesterCounts = $courses->mapWithKeys(function (Course $c) {
            $years = $c->duration_type === 'year' ? (int) $c->duration : max(1, (int) ceil($c->duration / 12));
            return [$c->id => $years * $c->effectiveSemestersPerYear()];
        });

        return view('institute.master.sms.broadcasts.create', compact(
            'templates', 'courses', 'streams', 'staffRoles', 'typeLabels', 'courseTypes',
            'templatesForJs', 'autoVarsStudent', 'autoVarsStaff', 'courseSemesterCounts', 'overridableVars'
        ));
    }

    public function store(Request $request)
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'audience_type'             => 'required|in:' . SmsBroadcast::AUDIENCE_STAFF . ',' . SmsBroadcast::AUDIENCE_STUDENT,
            'sms_template_id'           => 'required|integer',
            'target_course_ids'         => 'nullable|array',
            'target_course_ids.*'       => 'integer',
            'target_stream_ids'         => 'nullable|array',
            'target_stream_ids.*'       => 'integer',
            'target_semesters'          => 'nullable|array',
            'target_semesters.*'        => 'integer|min:1|max:12',
            'target_staff_role_ids'     => 'nullable|array',
            'target_staff_role_ids.*'   => 'integer',
            'recipient_mode'            => 'required|in:' . SmsBroadcast::RECIPIENT_MODE_ALL . ',' . SmsBroadcast::RECIPIENT_MODE_SPECIFIC,
            'specific_recipient_ids'    => 'nullable|array',
            'specific_recipient_ids.*'  => 'integer',
            'link_notice'               => 'nullable|boolean',
            'notice_title'              => 'nullable|required_if:link_notice,1|string|max:255',
            'notice_body'               => 'nullable|required_if:link_notice,1|string|max:10000',
        ]);

        $template = SmsTemplate::where('institute_id', $instituteId)
            ->where('id', $validated['sms_template_id'])
            ->whereIn('type', array_keys(self::BROADCASTABLE_TYPES))
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return back()->withInput()->with('error', 'Template nahi mila ya inactive hai.');
        }

        if ($validated['recipient_mode'] === SmsBroadcast::RECIPIENT_MODE_SPECIFIC && empty($validated['specific_recipient_ids'])) {
            return back()->withInput()->with('error', 'Specific mode me kam se kam ek recipient select karo.');
        }

        // Only capture values for variables this template declares that AREN'T auto-filled per
        // recipient (name/course/etc. come from each student's own record at send time) — except
        // a var like {mobile} that's overridable: admin can still type one fixed value (e.g. the
        // college office number) for the whole broadcast instead of each recipient's own; leaving
        // it blank keeps the normal per-recipient auto-fill.
        $autoVars        = SmsBroadcastTargeting::recipientAutoVarNames($validated['audience_type']);
        $overridableVars = SmsBroadcastTargeting::overridableAutoVarNames();
        $values          = [];
        foreach ($template->variable_names_array as $varName) {
            $isAuto        = in_array($varName, $autoVars, true);
            $isOverridable = in_array($varName, $overridableVars, true);
            if ($isAuto && !$isOverridable) continue;

            $typed = (string) $request->input("template_values.{$varName}", '');
            if ($isAuto && $isOverridable && $typed === '') continue;

            $values[$varName] = $typed;
        }

        $sanitized = SmsBroadcastTargeting::sanitizeFilters($instituteId, $validated['audience_type'], $validated);

        // Specific-recipient ids are re-validated against the actual reachable pool here —
        // never trust posted ids directly, even though the pool is already institute-scoped.
        $specificIds = null;
        if ($validated['recipient_mode'] === SmsBroadcast::RECIPIENT_MODE_SPECIFIC) {
            $specificIds = SmsBroadcastTargeting::baseQuery($instituteId, $validated['audience_type'])
                ->whereIn('id', $validated['specific_recipient_ids'])
                ->pluck('id')->all();
        }

        $totalRecipients = SmsBroadcastTargeting::resolve($instituteId, $validated['audience_type'], array_merge(
            $validated,
            $sanitized,
            ['specific_recipient_ids' => $specificIds]
        ))->count();

        // The linked Notice (if requested) is only staged here — it's actually created at
        // send()-confirmation time, not now, so nothing goes out just from saving a draft.
        $broadcast = SmsBroadcast::create([
            'institute_id'           => $instituteId,
            'sms_template_id'        => $template->id,
            'template_values'        => $values,
            'audience_type'          => $validated['audience_type'],
            'target_course_ids'      => $sanitized['target_course_ids'],
            'target_stream_ids'      => $sanitized['target_stream_ids'],
            'target_semesters'       => $sanitized['target_semesters'],
            'target_staff_role_ids'  => $sanitized['target_staff_role_ids'],
            'recipient_mode'         => $validated['recipient_mode'],
            'specific_recipient_ids' => $specificIds,
            'notice_title'           => $request->boolean('link_notice') ? $validated['notice_title'] : null,
            'notice_body'            => $request->boolean('link_notice') ? $validated['notice_body'] : null,
            'status'                 => SmsBroadcast::STATUS_DRAFT,
            'total_recipients'       => $totalRecipients,
            'created_by_user_id'     => Auth::id(),
        ]);

        return redirect()->route('master.sms.broadcasts.show', $broadcast)
            ->with('success', "Draft ban gaya ({$totalRecipients} recipients). Review karke send karo.");
    }

    public function show(SmsBroadcast $broadcast)
    {
        abort_if($broadcast->institute_id !== $this->instituteId(), 403);

        $broadcast->load('smsTemplate', 'linkedNotice');
        $recipientCount = $broadcast->status === SmsBroadcast::STATUS_DRAFT
            ? $broadcast->resolveRecipientQuery()->count()
            : $broadcast->total_recipients;

        $targetingLabels = [];
        if (!empty($broadcast->target_course_ids)) {
            $targetingLabels[] = 'Courses: ' . Course::whereIn('id', $broadcast->target_course_ids)->pluck('name')->implode(', ');
        }
        if (!empty($broadcast->target_stream_ids)) {
            $targetingLabels[] = 'Streams: ' . CourseStream::whereIn('id', $broadcast->target_stream_ids)->pluck('name')->implode(', ');
        }
        if (!empty($broadcast->target_semesters)) {
            $targetingLabels[] = 'Semesters: ' . collect($broadcast->target_semesters)->sort()->implode(', ');
        }
        if (!empty($broadcast->target_staff_role_ids)) {
            $targetingLabels[] = 'Roles: ' . StaffRole::whereIn('id', $broadcast->target_staff_role_ids)->pluck('name')->implode(', ');
        }

        // Sample message preview — shared values are already known; per-recipient values
        // (name/course/etc.) can't be shown for one generic sample, so they stay bracketed —
        // unless an overridable one (e.g. {mobile}) was given a fixed override value, in which
        // case that's exactly what every recipient will actually get.
        $autoVars     = SmsBroadcastTargeting::recipientAutoVarNames($broadcast->audience_type);
        $sampleText   = $broadcast->smsTemplate->content ?? '';
        foreach ($broadcast->smsTemplate->variable_names_array ?? [] as $varName) {
            $isAuto       = in_array($varName, $autoVars, true);
            $hasOverride  = array_key_exists($varName, $broadcast->template_values ?? []);
            $sampleText = str_replace(
                '{' . $varName . '}',
                ($isAuto && !$hasOverride) ? '[' . $varName . ']' : ($broadcast->template_values[$varName] ?? ''),
                $sampleText
            );
        }

        // Individual delivery rows — only meaningful once sending has actually started.
        $deliveryLogs = $broadcast->status === SmsBroadcast::STATUS_DRAFT
            ? null
            : $broadcast->smsLogs()->latest()->paginate(20, ['*'], 'logs_page');

        return view('institute.master.sms.broadcasts.show', compact('broadcast', 'recipientCount', 'targetingLabels', 'sampleText', 'deliveryLogs'));
    }

    // The actual "confirm & send" action — creates the linked Notice (if one was requested)
    // and queues the real SMS send right now, not at draft time.
    public function send(SmsBroadcast $broadcast)
    {
        abort_if($broadcast->institute_id !== $this->instituteId(), 403);

        if ($broadcast->status !== SmsBroadcast::STATUS_DRAFT) {
            return back()->with('error', 'Ye broadcast pehle hii send/queue ho chuka hai.');
        }

        $recipientCount = $broadcast->resolveRecipientQuery()->count();
        if ($recipientCount === 0) {
            return back()->with('error', 'Is targeting se koi recipient nahi mila — filters check karo.');
        }

        if ($broadcast->notice_title) {
            $notice = Notice::create([
                'institute_id'       => $broadcast->institute_id,
                'title'              => $broadcast->notice_title,
                'body'               => $broadcast->notice_body,
                'notice_type'        => 'general',
                'visible_to'         => [$broadcast->audience_type === SmsBroadcast::AUDIENCE_STAFF ? 'staff' : 'students'],
                'target_course_ids'  => $broadcast->audience_type === SmsBroadcast::AUDIENCE_STUDENT ? $broadcast->target_course_ids : null,
                'target_semesters'   => $broadcast->audience_type === SmsBroadcast::AUDIENCE_STUDENT ? $broadcast->target_semesters : null,
                'notice_date'        => now()->toDateString(),
                'is_active'          => true,
                'posted_by_user_id'  => Auth::id(),
            ]);
            $broadcast->linked_notice_id = $notice->id;
        }

        $broadcast->status           = SmsBroadcast::STATUS_QUEUED;
        $broadcast->total_recipients = $recipientCount;
        $broadcast->save();

        SendSmsBroadcastJob::dispatch($broadcast->id);

        return redirect()->route('master.sms.broadcasts.index')
            ->with('success', 'SMS bhejna shuru ho gaya — status "Send SMS" list me dikhega.');
    }

    public function destroy(SmsBroadcast $broadcast)
    {
        abort_if($broadcast->institute_id !== $this->instituteId(), 403);

        if ($broadcast->status !== SmsBroadcast::STATUS_DRAFT) {
            return back()->with('error', 'Sirf draft broadcasts delete kiye ja sakte hain.');
        }

        $broadcast->delete();

        return redirect()->route('master.sms.broadcasts.index')->with('success', 'Draft delete ho gaya.');
    }

    // Live recipient-count preview while composing — same resolver the send job uses later,
    // so the number an admin sees before confirming is exactly who receives the SMS.
    public function previewCount(Request $request)
    {
        $validated = $request->validate([
            'audience_type'             => 'required|in:' . SmsBroadcast::AUDIENCE_STAFF . ',' . SmsBroadcast::AUDIENCE_STUDENT,
            'target_course_ids'         => 'nullable|array',
            'target_course_ids.*'       => 'integer',
            'target_stream_ids'         => 'nullable|array',
            'target_stream_ids.*'       => 'integer',
            'target_semesters'          => 'nullable|array',
            'target_semesters.*'        => 'integer|min:1|max:12',
            'target_staff_role_ids'     => 'nullable|array',
            'target_staff_role_ids.*'   => 'integer',
            'recipient_mode'            => 'nullable|in:' . SmsBroadcast::RECIPIENT_MODE_ALL . ',' . SmsBroadcast::RECIPIENT_MODE_SPECIFIC,
            'specific_recipient_ids'    => 'nullable|array',
            'specific_recipient_ids.*'  => 'integer',
        ]);

        $count = SmsBroadcastTargeting::resolve($this->instituteId(), $validated['audience_type'], $validated)->count();

        return response()->json(['count' => $count]);
    }

    // Search within the current targeting filters' pool — powers the "specific recipients only"
    // picker so admins search by name instead of scrolling a full roster.
    public function searchRecipients(Request $request)
    {
        $validated = $request->validate([
            'audience_type'           => 'required|in:' . SmsBroadcast::AUDIENCE_STAFF . ',' . SmsBroadcast::AUDIENCE_STUDENT,
            'target_course_ids'       => 'nullable|array',
            'target_course_ids.*'     => 'integer',
            'target_stream_ids'       => 'nullable|array',
            'target_stream_ids.*'     => 'integer',
            'target_semesters'        => 'nullable|array',
            'target_semesters.*'      => 'integer|min:1|max:12',
            'target_staff_role_ids'   => 'nullable|array',
            'target_staff_role_ids.*' => 'integer',
            'q'                       => 'nullable|string|max:100',
        ]);

        $query = SmsBroadcastTargeting::resolve($this->instituteId(), $validated['audience_type'], $validated);

        if (!empty($validated['q'])) {
            $query->where('name', 'like', '%' . $validated['q'] . '%');
        }

        // A bare name+mobile list is hard to pick from when several students share a name —
        // show course/stream/semester (or role, for staff) so a single click is unambiguous.
        if ($validated['audience_type'] === SmsBroadcast::AUDIENCE_STUDENT) {
            $rows = $query->with('stream.course')->orderBy('name')->limit(50)
                ->get(['id', 'name', 'mobile', 'roll_no', 'current_semester', 'course_stream_id'])
                ->map(fn ($s) => [
                    'id'      => $s->id,
                    'name'    => $s->name,
                    'mobile'  => $s->mobile,
                    'details' => collect([
                        $s->stream?->course?->name,
                        $s->stream?->name,
                        $s->current_semester ? "Sem {$s->current_semester}" : null,
                        $s->roll_no ? "Roll {$s->roll_no}" : null,
                    ])->filter()->implode(' · '),
                ]);
        } else {
            $rows = $query->with('role')->orderBy('name')->limit(50)
                ->get(['id', 'name', 'mobile', 'staff_role_id', 'staff_uid'])
                ->map(fn ($s) => [
                    'id'      => $s->id,
                    'name'    => $s->name,
                    'mobile'  => $s->mobile,
                    'details' => collect([$s->role?->name, $s->staff_uid])->filter()->implode(' · '),
                ]);
        }

        return response()->json($rows->values());
    }
}
