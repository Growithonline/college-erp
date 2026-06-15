<?php

namespace App\Http\Controllers\Institute\Admission;

use App\Http\Controllers\Controller;
use App\Models\AdmissionDocument;
use App\Models\DocumentType;
use App\Models\DocumentUploadRule;
use App\Models\Student;
use App\Notifications\DocumentRejectedNotification;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdmissionDocumentController extends Controller
{
    // ─── Guard / context helpers ───────────────────────────────────────────────

    private function actorGuard(): string
    {
        foreach (['staff', 'center', 'partner', 'web'] as $guard) {
            if (auth()->guard($guard)->check()) return $guard;
        }
        return 'web';
    }

    private function actorUser()
    {
        $guard = $this->actorGuard();
        return auth()->guard($guard)->user();
    }

    private function instituteId(): int
    {
        return (int) $this->actorUser()->institute_id;
    }

    private function panelUserType(): string
    {
        return match ($this->actorGuard()) {
            'center'  => 'center',
            'partner' => 'partner',
            'staff'   => 'staff',
            default   => 'online',
        };
    }

    // ─── Permission checks ─────────────────────────────────────────────────────

    private function canUpload(): bool
    {
        $guard = $this->actorGuard();
        if ($guard === 'staff') {
            return auth()->guard('staff')->user()->hasPermission('document_upload');
        }
        return in_array($guard, ['web', 'center', 'partner']);
    }

    private function canVerify(): bool
    {
        $guard = $this->actorGuard();
        if ($guard === 'staff') {
            return auth()->guard('staff')->user()->hasPermission('document_verify');
        }
        return $guard === 'web'; // institute admin
    }

    private function canDelete(): bool
    {
        $guard = $this->actorGuard();
        if ($guard === 'staff') {
            return auth()->guard('staff')->user()->hasPermission('document_delete');
        }
        return $guard === 'web';
    }

    // ─── Required documents helper ─────────────────────────────────────────────

    public static function getRequiredDocumentTypes(int $courseId, string $userType, int $instituteId): array
    {
        $rules = DocumentUploadRule::where('course_id', $courseId)
            ->where('user_type', $userType)
            ->where('requirement', '!=', 'skip')
            ->with(['documentType' => fn($q) => $q->active()])
            ->get();

        return $rules->map(fn($r) => [
            'document_type'  => $r->documentType,
            'requirement'    => $r->requirement,
        ])->filter(fn($r) => $r['document_type'] !== null)->values()->all();
    }

    // ─── AJAX: get required docs for a course (used during admission form) ──────

    public function getForCourse(Request $request)
    {
        $request->validate(['course_id' => 'required|exists:courses,id']);

        $userType = $this->panelUserType();
        $docs     = self::getRequiredDocumentTypes($request->course_id, $userType, $this->instituteId());

        return response()->json(['documents' => $docs]);
    }

    // ─── Upload ────────────────────────────────────────────────────────────────

    public function upload(Request $request, Student $student)
    {
        abort_unless($this->canUpload(), 403, 'You do not have permission to upload documents.');
        abort_if($student->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'document_type_id' => 'required|exists:document_types,id',
            'file'             => 'required|file|max:10240', // 10MB absolute hard limit
        ]);

        $docType = DocumentType::where('id', $request->document_type_id)
            ->where('institute_id', $this->instituteId())
            ->where('status', true)
            ->firstOrFail();

        $fileKey = 'file_' . $docType->id;

        // Validate against document-type specific size
        $fileSizeKb = (int) ceil($request->file('file')->getSize() / 1024);
        if ($fileSizeKb > $docType->max_size_kb) {
            return back()->withErrors([$fileKey => "File size {$fileSizeKb} KB exceeds the maximum allowed {$docType->max_size_kb} KB."]);
        }

        // Validate format
        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        $allowed = array_map('trim', explode(',', $docType->allowed_formats));
        if (!in_array($ext, $allowed)) {
            return back()->withErrors([$fileKey => "Format .$ext is not allowed. Allowed formats: " . $docType->allowed_formats]);
        }

        // Delete old file if replacing
        $existing = AdmissionDocument::where('student_id', $student->id)
            ->where('document_type_id', $docType->id)
            ->first();

        if ($existing) {
            abort_unless($this->canUpload(), 403);
            // Only allow replacement if pending or rejected — not if approved
            if ($existing->isApproved() && $this->actorGuard() !== 'web') {
                return back()->withErrors([$fileKey => 'This document is already approved. Please contact the admin to replace it.']);
            }
            Storage::disk('public')->delete($existing->file_path);
            $existing->delete();
        }

        $actor     = $this->actorUser();
        $guard     = $this->actorGuard();
        $path      = $request->file('file')->store("admission-docs/{$student->institute_id}/{$student->id}", 'public');

        $doc = AdmissionDocument::create([
            'institute_id'       => $this->instituteId(),
            'student_id'         => $student->id,
            'document_type_id'   => $docType->id,
            'file_path'          => $path,
            'original_name'      => $request->file('file')->getClientOriginalName(),
            'mime_type'          => $request->file('file')->getMimeType(),
            'file_size_kb'       => $fileSizeKb,
            'uploaded_by_type'   => $guard,
            'uploaded_by_id'     => $actor->id,
            'verification_status'=> 'pending',
        ]);

        AuditLogService::log($this->instituteId(), 'admission_document', 'uploaded',
            "Document '{$docType->name}' uploaded for student {$student->name}", $doc);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'document' => $doc->load('documentType')]);
        }

        return back()->with('success', "'{$docType->name}' uploaded successfully.");
    }

    // ─── Verify / Approve ──────────────────────────────────────────────────────

    public function verify(Request $request, AdmissionDocument $document)
    {
        abort_unless($this->canVerify(), 403, 'You do not have permission to verify documents.');
        abort_if($document->institute_id !== $this->instituteId(), 403);

        $document->update([
            'verification_status' => 'approved',
            'verified_by'         => $this->actorUser()->id,
            'verified_at'         => now(),
            'rejection_reason'    => null,
        ]);

        AuditLogService::log($this->instituteId(), 'admission_document', 'approved',
            "Document '{$document->documentType->name}' approved", $document);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Document approved successfully.');
    }

    // ─── Reject ────────────────────────────────────────────────────────────────

    public function reject(Request $request, AdmissionDocument $document)
    {
        abort_unless($this->canVerify(), 403, 'You do not have permission to verify documents.');
        abort_if($document->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
            'send_notification'=> 'boolean',
        ]);

        $document->update([
            'verification_status' => 'rejected',
            'verified_by'         => $this->actorUser()->id,
            'verified_at'         => now(),
            'rejection_reason'    => $request->rejection_reason,
        ]);

        AuditLogService::log($this->instituteId(), 'admission_document', 'rejected',
            "Document '{$document->documentType->name}' rejected", $document,
            ['reason' => $request->rejection_reason]);

        // Send notification if admin opted in
        if ($request->boolean('send_notification')) {
            $institute = $document->institute;
            if ($institute->doc_rejection_notify) {
                $student = $document->student;
                if ($student->email) {
                    try {
                        $student->notify(new DocumentRejectedNotification($document));
                    } catch (\Throwable) {
                        // Notification failure should not break the flow
                    }
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Document rejected successfully.');
    }

    // ─── Delete ────────────────────────────────────────────────────────────────

    public function destroy(AdmissionDocument $document)
    {
        abort_unless($this->canDelete(), 403);
        abort_if($document->institute_id !== $this->instituteId(), 403);

        Storage::disk('public')->delete($document->file_path);

        AuditLogService::log($this->instituteId(), 'admission_document', 'deleted',
            "Document '{$document->documentType->name}' deleted", null,
            ['student_id' => $document->student_id]);

        $document->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Document deleted successfully.');
    }

    // ─── Document Upload Intermediate Page ────────────────────────────────────

    public function uploadPage(Request $request, Student $student)
    {
        abort_if($student->institute_id !== $this->instituteId(), 403);

        $student->load(['stream.course', 'session']);
        $courseId  = $student->stream?->course_id;
        $userType  = $this->panelUserType();

        $rules = $courseId
            ? self::getRequiredDocumentTypes($courseId, $userType, $this->instituteId())
            : [];

        $uploaded = AdmissionDocument::where('student_id', $student->id)
            ->with('documentType')
            ->get()
            ->keyBy('document_type_id');

        $docSetting = session('doc_upload_setting', 'optional');
        $nextUrl    = session('doc_upload_next');

        // Build route prefix for form actions
        $guard = $this->actorGuard();
        $docRoutePrefix = match ($guard) {
            'staff'   => 'staff.admission.documents',
            'center'  => 'center.admission.documents',
            'partner' => 'partner.admission.documents',
            default   => 'admission.documents',
        };

        // Default "continue" destination = student profile
        $profileUrl = route('admissions.show', $student->id);

        return view('institute.admission.upload-documents', compact(
            'student', 'rules', 'uploaded', 'docSetting', 'docRoutePrefix', 'nextUrl', 'profileUrl'
        ));
    }

    // ─── Preview / Download ────────────────────────────────────────────────────

    public function show(AdmissionDocument $document)
    {
        abort_if($document->institute_id !== $this->instituteId(), 403);

        // Check view permission
        $guard = $this->actorGuard();
        if ($guard === 'staff' && !auth()->guard('staff')->user()->hasPermission('document_view')) {
            abort(403);
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File nahi mila.');
        }

        return Storage::disk('public')->response($document->file_path, $document->original_name);
    }
}
