<?php

namespace App\Http\Controllers\Institute\Certificate;

use App\Http\Controllers\Controller;
use App\Models\CertificateSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CertificateSettingController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index(): View
    {
        $settings = CertificateSetting::firstOrCreate(
            ['institute_id' => $this->instituteId()],
            ['theme' => 'classic', 'primary_color' => '#1e3a5f']
        );

        return view('institute.certificate.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'header_line1'          => 'nullable|string|max:200',
            'header_line2'          => 'nullable|string|max:200',
            'header_line3'          => 'nullable|string|max:200',
            'principal_name'        => 'nullable|string|max:150',
            'principal_designation' => 'nullable|string|max:100',
            'registrar_name'        => 'nullable|string|max:150',
            'registrar_designation' => 'nullable|string|max:100',
            'theme'                 => 'required|in:classic,colored,minimal',
            'primary_color'         => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'logo'                  => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
            'seal_image'            => 'nullable|image|mimes:png,jpg,jpeg|max:512',
            'principal_signature'   => 'nullable|image|mimes:png,jpg,jpeg|max:512',
            'registrar_signature'   => 'nullable|image|mimes:png,jpg,jpeg|max:512',
        ]);

        $settings = CertificateSetting::firstOrCreate(['institute_id' => $this->instituteId()]);

        $imageFields = ['logo', 'seal_image', 'principal_signature', 'registrar_signature'];

        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                if ($settings->$field) {
                    Storage::disk('public')->delete($settings->$field);
                }
                $validated[$field] = $request->file($field)->store('certificates', 'public');
            } else {
                unset($validated[$field]);
            }
        }

        $settings->update($validated);

        return back()->with('success', 'Certificate settings save ho gayi hain.');
    }

    public function removeImage(Request $request, string $field): RedirectResponse
    {
        abort_unless(in_array($field, ['logo', 'seal_image', 'principal_signature', 'registrar_signature']), 422);

        $settings = CertificateSetting::where('institute_id', $this->instituteId())->firstOrFail();

        if ($settings->$field) {
            Storage::disk('public')->delete($settings->$field);
            $settings->update([$field => null]);
        }

        return back()->with('success', ucfirst(str_replace('_', ' ', $field)) . ' remove ho gaya.');
    }
}
