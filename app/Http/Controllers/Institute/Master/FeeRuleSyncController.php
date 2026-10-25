<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Services\AuditLogService;
use App\Services\FeeRuleSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Admin-only preview/apply of FeeRuleSyncService from the Course Fee Structure screen: shows which
// live-session students are charged differently from today's course-fee rules, and — after an
// explicit confirmation — books the difference. Apply recomputes everything itself; it never
// trusts what the preview showed.
class FeeRuleSyncController extends Controller
{
    private function context(Request $request): array
    {
        abort_unless(auth()->guard('web')->check(), 403);

        $instituteId = (int) auth()->user()->institute_id;

        $data = $request->validate([
            'session_id'      => ['required', Rule::exists('academic_sessions', 'id')->where('institute_id', $instituteId)],
            'course_id'       => ['nullable', Rule::exists('courses', 'id')->where('institute_id', $instituteId)],
            'from'            => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'include_new'     => ['nullable', 'boolean'],
            'include_removed' => ['nullable', 'boolean'],
        ]);

        $opts = [
            'course'          => !empty($data['course_id']) ? (int) $data['course_id'] : null,
            'from'            => isset($data['from']) && $data['from'] !== '' ? (float) $data['from'] : null,
            'include_new'     => (bool) ($data['include_new'] ?? false),
            'include_removed' => (bool) ($data['include_removed'] ?? false),
        ];

        return [$instituteId, (int) $data['session_id'], $opts];
    }

    public function preview(Request $request)
    {
        [$instituteId, $sessionId, $opts] = $this->context($request);

        set_time_limit(300);
        $report = FeeRuleSyncService::run($instituteId, $sessionId, $opts, false);

        return view('institute.master.fee.course-fee-rules.sync', [
            'report'  => $report,
            'session' => AcademicSession::find($sessionId),
            'course'  => $opts['course'] ? Course::find($opts['course']) : null,
            'opts'    => $opts,
        ]);
    }

    public function apply(Request $request)
    {
        [$instituteId, $sessionId, $opts] = $this->context($request);

        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Please tick the confirmation box before applying.']);

        set_time_limit(300);
        $report = FeeRuleSyncService::run($instituteId, $sessionId, $opts, true);

        AuditLogService::log(
            $instituteId,
            'fee_rule',
            'synced_to_students',
            "Fee rules synced to live-session students: {$report['changed']} adjustment(s), +"
                . number_format($report['increase'], 2) . ' / -' . number_format($report['decrease'], 2),
            null,
            ['session_id' => $sessionId, 'options' => $opts, 'checked' => $report['checked'], 'changed' => $report['changed'], 'skipped' => $report['skipped']]
        );

        return redirect()
            ->route('master.fee-structure.course-fees.sync', array_filter([
                'session_id'      => $sessionId,
                'course_id'       => $opts['course'],
                'from'            => $opts['from'],
                'include_new'     => $opts['include_new'] ? 1 : null,
                'include_removed' => $opts['include_removed'] ? 1 : null,
            ], fn($v) => $v !== null))
            ->with('success', "Applied: {$report['changed']} adjustment(s) across {$report['checked']} student(s) (+"
                . number_format($report['increase'], 2) . ' / -' . number_format($report['decrease'], 2)
                . "), {$report['skipped']} skipped.");
    }
}
