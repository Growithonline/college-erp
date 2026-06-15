<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\CertificateType;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class CertificateService
{
    public function buildPlaceholders(Student $student, Certificate $certificate): array
    {
        $stream  = $student->stream;
        $course  = $stream?->course;
        $session = $student->session;

        return [
            '{{student_name}}'      => $student->name ?? '',
            '{{father_name}}'       => $student->father_name ?? '',
            '{{mother_name}}'       => $student->mother_name ?? '',
            '{{enrollment_no}}'     => $student->enrollment_no ?? $student->student_uid ?? '',
            '{{roll_no}}'           => $student->roll_no ?? '',
            '{{course_name}}'       => $course?->name ?? '',
            '{{stream_name}}'       => $stream?->name ?? '',
            '{{semester}}'          => $student->current_semester ? 'Semester ' . $student->current_semester : '',
            '{{admission_date}}'    => $student->admission_date ? Carbon::parse($student->admission_date)->format('d/m/Y') : '',
            '{{current_date}}'      => Carbon::now()->format('d/m/Y'),
            '{{academic_session}}'  => $session?->name ?? '',
            '{{certificate_number}}' => $certificate->certificate_number,
            '{{institute_name}}'    => $student->institute?->name ?? '',
        ];
    }

    public function renderBody(CertificateType $type, array $placeholders): string
    {
        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $type->body_template
        );
    }

    public function generatePdf(Certificate $certificate): \Barryvdh\DomPDF\PDF
    {
        $certificate->load(['student.stream.course', 'student.session', 'student.institute', 'certificateType']);

        $settings    = CertificateSetting::where('institute_id', $certificate->institute_id)->first()
                       ?? new CertificateSetting(['theme' => 'classic', 'primary_color' => '#1e3a5f']);

        $placeholders = $this->buildPlaceholders($certificate->student, $certificate);
        $bodyHtml     = $this->renderBody($certificate->certificateType, $placeholders);

        $templateView = 'institute.certificate.pdf.' . $settings->theme;

        $pdf = Pdf::loadView($templateView, [
            'certificate' => $certificate,
            'settings'    => $settings,
            'bodyHtml'    => $bodyHtml,
            'student'     => $certificate->student,
            'type'        => $certificate->certificateType,
        ])->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function generateNumber(int $instituteId, string $slug): string
    {
        $year  = Carbon::now()->format('Y');
        $count = Certificate::whereHas('certificateType', fn($q) => $q->where('slug', $slug))
                     ->where('institute_id', $instituteId)
                     ->whereYear('issued_at', $year)
                     ->count() + 1;

        return strtoupper($slug) . '/' . $year . '/' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
