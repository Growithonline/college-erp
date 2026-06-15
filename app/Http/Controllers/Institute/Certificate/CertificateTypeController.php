<?php

namespace App\Http\Controllers\Institute\Certificate;

use App\Http\Controllers\Controller;
use App\Models\CertificateType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateTypeController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index(): View
    {
        $types = CertificateType::forInstitute($this->instituteId())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('institute.certificate.types.index', compact('types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'slug'          => ['required', 'string', 'max:30', 'alpha_dash',
                                \Illuminate\Validation\Rule::unique('certificate_types')->where('institute_id', $instituteId)],
            'body_template' => 'required|string',
        ]);

        $validated['institute_id'] = $instituteId;
        $validated['sort_order']   = CertificateType::forInstitute($instituteId)->max('sort_order') + 1;

        CertificateType::create($validated);

        return back()->with('success', $validated['name'] . ' add ho gaya.');
    }

    public function update(Request $request, CertificateType $certificateType): RedirectResponse
    {
        abort_if($certificateType->institute_id !== $this->instituteId(), 403);

        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'body_template' => 'required|string',
        ]);

        $certificateType->update($validated);

        return back()->with('success', $certificateType->name . ' update ho gaya.');
    }

    public function toggle(CertificateType $certificateType): RedirectResponse
    {
        abort_if($certificateType->institute_id !== $this->instituteId(), 403);
        $certificateType->update(['is_active' => !$certificateType->is_active]);

        return back()->with('success', 'Status update ho gaya.');
    }

    public function destroy(CertificateType $certificateType): RedirectResponse
    {
        abort_if($certificateType->institute_id !== $this->instituteId(), 403);

        if ($certificateType->certificates()->exists()) {
            return back()->withErrors(['delete' => 'Is type ke certificates already issue ho chuke hain, delete nahi kar sakte.']);
        }

        $certificateType->delete();

        return back()->with('success', $certificateType->name . ' delete ho gaya.');
    }

    public function seed(): RedirectResponse
    {
        $instituteId = $this->instituteId();

        if (CertificateType::forInstitute($instituteId)->exists()) {
            return back()->with('info', 'Certificate types already setup hain.');
        }

        $defaults = $this->defaultTypes();
        foreach ($defaults as $i => $type) {
            CertificateType::create([
                'institute_id'  => $instituteId,
                'name'          => $type['name'],
                'slug'          => $type['slug'],
                'body_template' => $type['body_template'],
                'sort_order'    => $i + 1,
            ]);
        }

        return back()->with('success', count($defaults) . ' default certificate types load ho gaye.');
    }

    private function defaultTypes(): array
    {
        return [
            [
                'name' => 'Transfer Certificate',
                'slug' => 'tc',
                'body_template' => '<p>This is to certify that <strong>{{student_name}}</strong>, Son/Daughter of <strong>{{father_name}}</strong>, was a bonafide student of this institution.</p>

<p>He/She was admitted to <strong>{{course_name}}</strong> ({{stream_name}}) on <strong>{{admission_date}}</strong> and studied here up to <strong>{{current_date}}</strong>.</p>

<p>His/Her conduct and character during the stay in this institution was <strong>Good</strong>.</p>

<p>He/She bears a good moral character. This certificate is issued on his/her request for the purpose of admission in another institution.</p>

<p>We wish him/her all the best for future endeavours.</p>',
            ],
            [
                'name' => 'Character Certificate',
                'slug' => 'cc',
                'body_template' => '<p>This is to certify that <strong>{{student_name}}</strong>, Son/Daughter of <strong>{{father_name}}</strong>, Enrollment No. <strong>{{enrollment_no}}</strong>, is a bonafide student of <strong>{{course_name}}</strong> ({{stream_name}}) in this institution.</p>

<p>During his/her stay in this institution, his/her character and conduct have been <strong>Good</strong>. He/She has never been involved in any kind of misconduct or anti-social activities.</p>

<p>This certificate is issued on his/her request.</p>',
            ],
            [
                'name' => 'Bonafide Certificate',
                'slug' => 'bonafide',
                'body_template' => '<p>This is to certify that <strong>{{student_name}}</strong>, Son/Daughter of <strong>{{father_name}}</strong>, is a bonafide student of this institution.</p>

<p>He/She is currently enrolled in <strong>{{course_name}}</strong> ({{stream_name}}), Semester/Year <strong>{{semester}}</strong>, for the academic year <strong>{{academic_session}}</strong>.</p>

<p>His/Her Enrollment Number is <strong>{{enrollment_no}}</strong>.</p>

<p>This certificate is issued for the purpose of <em>[purpose]</em> on his/her request.</p>',
            ],
            [
                'name' => 'Migration Certificate',
                'slug' => 'migration',
                'body_template' => '<p>This is to certify that <strong>{{student_name}}</strong>, Son/Daughter of <strong>{{father_name}}</strong>, bearing Enrollment No. <strong>{{enrollment_no}}</strong>, was admitted to <strong>{{course_name}}</strong> in this institution on <strong>{{admission_date}}</strong>.</p>

<p>He/She has now left this institution and his/her original documents are enclosed herewith.</p>

<p>This migration certificate is issued to enable him/her to join another recognized university/institution.</p>',
            ],
        ];
    }
}
