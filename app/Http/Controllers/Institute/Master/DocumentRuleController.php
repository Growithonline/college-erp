<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\DocumentUploadRule;
use Illuminate\Http\Request;

class DocumentRuleController extends Controller
{
    private function instituteId(): int
    {
        return (int) auth()->user()->institute_id;
    }

    public function index()
    {
        $instituteId = $this->instituteId();

        $courses = Course::where('institute_id', $instituteId)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        return view('institute.master.document-rules.index', compact('courses'));
    }

    public function show(Course $course)
    {
        $instituteId = $this->instituteId();
        abort_if($course->institute_id !== $instituteId, 403);

        $categories = DocumentCategory::forInstitute($instituteId)
            ->active()
            ->with(['documentTypes' => fn($q) => $q->active()])
            ->orderBy('name')
            ->get();

        // Existing rules: [user_type][document_type_id] = requirement (admission_type='all' — every admission type)
        $rules = DocumentUploadRule::where('course_id', $course->id)
            ->where('admission_type', 'all')
            ->get()
            ->groupBy('user_type')
            ->map(fn($group) => $group->pluck('requirement', 'document_type_id'));

        // Lateral Entry only ever happens via the staff/institute panel, so it's a single
        // extra column layered on top of the 'staff' user_type — optional, defaults to
        // whatever the 'staff' column above already says if left unset.
        $lateralRules = DocumentUploadRule::where('course_id', $course->id)
            ->where('user_type', 'staff')
            ->where('admission_type', 'lateral')
            ->pluck('requirement', 'document_type_id');

        $userTypes = DocumentUploadRule::USER_TYPES;

        return view('institute.master.document-rules.show', compact('course', 'categories', 'rules', 'userTypes', 'lateralRules'));
    }

    public function save(Request $request, Course $course)
    {
        $instituteId = $this->instituteId();
        abort_if($course->institute_id !== $instituteId, 403);

        $request->validate([
            'rules'                     => 'nullable|array',
            'rules.*.*'                 => 'in:required,optional,skip',
            'lateral_rules'             => 'nullable|array',
            'lateral_rules.*'           => 'nullable|in:required,optional,skip',
        ]);

        $incoming = $request->input('rules', []); // [user_type][doc_type_id] = requirement

        foreach (DocumentUploadRule::USER_TYPES as $userType) {
            $typeRules = $incoming[$userType] ?? [];

            foreach ($typeRules as $docTypeId => $requirement) {
                // Verify doc type belongs to this institute
                $docTypeExists = DocumentType::where('id', $docTypeId)
                    ->where('institute_id', $instituteId)
                    ->exists();

                if (!$docTypeExists) continue;

                DocumentUploadRule::updateOrCreate(
                    [
                        'course_id'        => $course->id,
                        'document_type_id' => $docTypeId,
                        'user_type'        => $userType,
                        'admission_type'   => 'all',
                    ],
                    [
                        'institute_id' => $instituteId,
                        'requirement'  => $requirement,
                    ]
                );
            }
        }

        // Optional Lateral Entry overrides (staff panel only) — "Default" (empty) means
        // it just falls back to the 'staff' column's rule above, so we delete any
        // existing override rather than storing an empty requirement.
        foreach ($request->input('lateral_rules', []) as $docTypeId => $requirement) {
            $docTypeExists = DocumentType::where('id', $docTypeId)
                ->where('institute_id', $instituteId)
                ->exists();

            if (!$docTypeExists) continue;

            $key = [
                'course_id'        => $course->id,
                'document_type_id' => $docTypeId,
                'user_type'        => 'staff',
                'admission_type'   => 'lateral',
            ];

            if (empty($requirement)) {
                DocumentUploadRule::where($key)->delete();
                continue;
            }

            DocumentUploadRule::updateOrCreate($key, [
                'institute_id' => $instituteId,
                'requirement'  => $requirement,
            ]);
        }

        return back()->with('success', 'Document rules saved for "' . $course->name . '".');
    }

    public function notificationSettings()
    {
        $institute = auth()->user()->institute;
        return view('institute.master.document-rules.notification-settings', compact('institute'));
    }

    public function saveNotificationSettings(Request $request)
    {
        $request->validate([
            'doc_rejection_notify'   => 'boolean',
            'doc_rejection_channels' => 'nullable|array',
            'doc_rejection_channels.*' => 'in:email,sms',
            'doc_rejection_trigger'  => 'required|in:per_document,final_only',
        ]);

        $institute = auth()->user()->institute;
        $institute->update([
            'doc_rejection_notify'   => $request->boolean('doc_rejection_notify'),
            'doc_rejection_channels' => $request->input('doc_rejection_channels') ? implode(',', $request->doc_rejection_channels) : null,
            'doc_rejection_trigger'  => $request->doc_rejection_trigger,
        ]);

        return back()->with('success', 'Notification settings saved.');
    }
}
