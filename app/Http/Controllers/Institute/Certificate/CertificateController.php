<?php

namespace App\Http\Controllers\Institute\Certificate;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\CertificateType;
use App\Models\Student;
use App\Services\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function __construct(private CertificateService $service) {}

    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    // Phase 6 — List all issued certificates
    public function index(Request $request): View
    {
        $instituteId = $this->instituteId();

        $types = CertificateType::forInstitute($instituteId)->active()->orderBy('name')->get();

        $query = Certificate::forInstitute($instituteId)
            ->with(['student.stream.course', 'certificateType', 'issuedBy'])
            ->orderByDesc('issued_at');

        if ($request->filled('type_id')) {
            $query->where('certificate_type_id', $request->type_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('student', fn($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('enrollment_no', 'like', "%$s%")
                ->orWhere('student_uid', 'like', "%$s%"));
        }
        if ($request->filled('from_date')) {
            $query->whereDate('issued_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('issued_at', '<=', $request->to_date);
        }

        $certificates = $query->paginate(25)->withQueryString();

        return view('institute.certificate.index', compact('certificates', 'types'));
    }

    // Phase 4 — Show issue form
    public function create(Request $request): View
    {
        $instituteId = $this->instituteId();
        $types       = CertificateType::forInstitute($instituteId)->active()->orderBy('name')->get();
        $sessions    = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();

        $student = null;
        if ($request->filled('student_id')) {
            $student = Student::where('institute_id', $instituteId)->find($request->student_id);
        }

        return view('institute.certificate.create', compact('types', 'sessions', 'student'));
    }

    // Phase 4 — Preview (AJAX or form post)
    public function preview(Request $request)
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'student_id'          => 'required|integer',
            'certificate_type_id' => 'required|integer',
            'remarks'             => 'nullable|string|max:500',
        ]);

        $student = Student::where('institute_id', $instituteId)
            ->with(['stream.course', 'session', 'institute'])
            ->findOrFail($validated['student_id']);

        abort_if(
            $student->status === 'pending',
            422,
            "This student's admission is pending approval. Certificates cannot be issued until the admission is approved."
        );

        $type = CertificateType::forInstitute($instituteId)->findOrFail($validated['certificate_type_id']);

        $tempCert = new Certificate([
            'certificate_number' => $this->service->generateNumber($instituteId, $type->slug),
            'issued_at'          => now(),
            'institute_id'       => $instituteId,
        ]);
        $tempCert->student          = $student;
        $tempCert->certificateType  = $type;

        $placeholders = $this->service->buildPlaceholders($student, $tempCert);
        $bodyHtml     = $this->service->renderBody($type, $placeholders);

        $settings = CertificateSetting::firstOrCreate(
            ['institute_id' => $instituteId],
            ['theme' => 'classic', 'primary_color' => '#1e3a5f']
        );

        return view('institute.certificate.preview', compact(
            'student', 'type', 'settings', 'bodyHtml', 'tempCert', 'validated'
        ));
    }

    // Phase 4 — Issue (store)
    public function store(Request $request): RedirectResponse
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'student_id'          => 'required|integer',
            'certificate_type_id' => 'required|integer',
            'remarks'             => 'nullable|string|max:500',
        ]);

        $student = Student::where('institute_id', $instituteId)->findOrFail($validated['student_id']);

        abort_if(
            $student->status === 'pending',
            422,
            "This student's admission is pending approval. Certificates cannot be issued until the admission is approved."
        );

        $type    = CertificateType::forInstitute($instituteId)->findOrFail($validated['certificate_type_id']);

        $certificate = Certificate::create([
            'institute_id'        => $instituteId,
            'student_id'          => $student->id,
            'certificate_type_id' => $type->id,
            'academic_session_id' => $student->academic_session_id,
            'certificate_number'  => $this->service->generateNumber($instituteId, $type->slug),
            'status'              => 'issued',
            'remarks'             => $validated['remarks'] ?? null,
            'issued_by'           => auth()->id(),
            'issued_at'           => now(),
        ]);

        return redirect()->route('certificate.download', $certificate)
            ->with('success', 'Certificate issued successfully: ' . $certificate->certificate_number);
    }

    // Phase 5 — Download PDF
    public function download(Certificate $certificate)
    {
        abort_if($certificate->institute_id !== $this->instituteId(), 403);
        abort_if($certificate->status === 'cancelled', 422, 'This certificate has been cancelled.');

        $pdf = $this->service->generatePdf($certificate);
        $filename = $certificate->certificate_number . '.pdf';

        return $pdf->download(str_replace('/', '-', $filename));
    }

    // Phase 6 — Inline view in browser
    public function show(Certificate $certificate)
    {
        abort_if($certificate->institute_id !== $this->instituteId(), 403);
        abort_if($certificate->status === 'cancelled', 422, 'This certificate has been cancelled.');

        $pdf = $this->service->generatePdf($certificate);
        $filename = $certificate->certificate_number . '.pdf';

        return $pdf->stream(str_replace('/', '-', $filename));
    }

    // Phase 6 — Cancel
    public function cancel(Certificate $certificate): RedirectResponse
    {
        abort_if($certificate->institute_id !== $this->instituteId(), 403);

        if ($certificate->status === 'cancelled') {
            return back()->with('info', 'Certificate already cancelled hai.');
        }

        $certificate->update(['status' => 'cancelled']);

        return back()->with('success', $certificate->certificate_number . ' cancel ho gaya.');
    }

    // AJAX student search for certificate issue form
    public function searchStudent(Request $request)
    {
        $q           = $request->get('q', '');
        $instituteId = $this->instituteId();

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $students = Student::where('institute_id', $instituteId)
            ->where('status', '!=', 'pending')
            ->with(['stream.course'])
            ->where(fn($query) => $query
                ->where('name', 'like', "%$q%")
                ->orWhere('enrollment_no', 'like', "%$q%")
                ->orWhere('student_uid', 'like', "%$q%")
                ->orWhere('roll_no', 'like', "%$q%")
                ->orWhere('father_name', 'like', "%$q%")
                ->orWhere('mobile', 'like', "%$q%"))
            ->orderBy('name')
            ->limit(15)
            ->get();

        return response()->json($students->map(fn($s) => [
            'id'          => $s->id,
            'name'        => $s->name,
            'student_uid' => $s->student_uid,
            'father_name' => $s->father_name,
            'mobile'      => $s->mobile,
            'course'      => $s->stream?->course?->name,
            'stream'      => $s->stream?->name,
            'semester'    => $s->current_semester,
        ]));
    }
}
