<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\AdmissionFormSetting;
use Illuminate\Http\Request;

class AdmissionFormController extends Controller
{
    private const FORM_TYPES = [
        'admission' => [
            'label'       => 'Admission Form',
            'icon'        => 'bi-file-earmark-person',
            'color'       => 'primary',
            'description' => 'Full admission form filled by staff',
        ],
        'quick' => [
            'label'       => 'Quick Registration',
            'icon'        => 'bi-lightning',
            'color'       => 'warning',
            'description' => 'Fast registration — basic fields only',
        ],
        'online' => [
            'label'       => 'Online Admission',
            'icon'        => 'bi-globe',
            'color'       => 'success',
            'description' => 'Student fills online — institute approves',
        ],
        'receipt' => [
            'label'       => 'Fee Receipt',
            'icon'        => 'bi-receipt',
            'color'       => 'info',
            'description' => 'Configure the fee receipt layout',
        ],
    ];

    // ─── SECTIONS per form type ────────────────────────────────────────────

    private const ADMISSION_SECTIONS = [
        'office' => [
            'label' => 'Office Details',
            'icon'  => 'bi-briefcase',
            'section_enabled' => true,
            'fields' => [
                ['key' => 'form_no',            'label' => 'Serial No.',                'enabled' => true,  'required' => false, 'readonly' => true],
                ['key' => 'institute_form_no',  'label' => 'Form No.',                  'enabled' => false, 'required' => false],
                ['key' => 'sr_no',              'label' => 'Student Registration No.',  'enabled' => true,  'required' => false],
                ['key' => 'enrollment_no',      'label' => 'Enrollment No.',            'enabled' => false, 'required' => false],
                ['key' => 'roll_no',            'label' => 'Roll No.',                  'enabled' => false, 'required' => false],
                ['key' => 'exam_form_no',       'label' => 'Exam Form No.',             'enabled' => false, 'required' => false],
                ['key' => 'uin_no',             'label' => 'UIN No.',                   'enabled' => false, 'required' => false],
                ['key' => 'reference_no',       'label' => 'Reference No.',             'enabled' => false, 'required' => false],
                ['key' => 'admission_type',     'label' => 'Admission Type',            'enabled' => true,  'required' => false],
                ['key' => 'admission_source',   'label' => 'Admission Source',          'enabled' => true,  'required' => false],
                ['key' => 'gap_year',           'label' => 'Gap Year',                  'enabled' => true,  'required' => false],
                ['key' => 'admission_date',     'label' => 'Admission Date',            'enabled' => true,  'required' => false],
                ['key' => 'submitted_date',     'label' => 'Submitted Date',            'enabled' => true,  'required' => false],
                ['key' => 'academic_session',   'label' => 'Academic Session',          'enabled' => true,  'required' => false, 'readonly' => true],
            ],
        ],
        'personal' => [
            'label' => 'Personal Details',
            'icon'  => 'bi-person',
            'section_enabled' => true,
            'fields' => [
                ['key' => 'photo',            'label' => 'Photo Upload',        'enabled' => true,  'required' => false],
                ['key' => 'name',             'label' => 'Student Name',        'enabled' => true,  'required' => true],
                ['key' => 'father_name',      'label' => 'Father Name',         'enabled' => true,  'required' => false],
                ['key' => 'father_mobile',    'label' => 'Father Mobile',       'enabled' => false, 'required' => false],
                ['key' => 'mother_name',      'label' => 'Mother Name',         'enabled' => true,  'required' => false],
                ['key' => 'dob',              'label' => 'Date of Birth',       'enabled' => true,  'required' => false],
                ['key' => 'gender',           'label' => 'Gender',              'enabled' => true,  'required' => false],
                ['key' => 'mobile',           'label' => 'Mobile No.',          'enabled' => true,  'required' => true],
                ['key' => 'email',            'label' => 'Email Address',       'enabled' => true,  'required' => false],
                ['key' => 'guardian_mobile',   'label' => 'Guardian Mobile',     'enabled' => false, 'required' => false],
                ['key' => 'guardian_name',     'label' => 'Guardian Name',       'enabled' => false, 'required' => false],
                ['key' => 'guardian_relation', 'label' => 'Guardian Relation',   'enabled' => false, 'required' => false],
                ['key' => 'religion',          'label' => 'Religion',            'enabled' => true,  'required' => false],
                ['key' => 'category',          'label' => 'Category',            'enabled' => true,  'required' => false],
                ['key' => 'special_category',  'label' => 'Special Category',    'enabled' => false, 'required' => false],
                ['key' => 'nationality',       'label' => 'Nationality',         'enabled' => false, 'required' => false],
                ['key' => 'aadhar_no',         'label' => 'Aadhar Card No.',     'enabled' => true,  'required' => false],
                ['key' => 'apaar_no',          'label' => 'APAAR No.',           'enabled' => false, 'required' => false],
                ['key' => 'student_type',      'label' => 'Student Type',        'enabled' => true,  'required' => false],
                ['key' => 'marital_status',    'label' => 'Marital Status',      'enabled' => false, 'required' => false],
            ],
        ],
        'address' => [
            'label' => 'Address Details',
            'icon'  => 'bi-geo-alt',
            'section_enabled' => true,
            'fields' => [
                ['key' => 'perm_village',  'label' => 'Village/City',           'enabled' => true,  'required' => false],
                ['key' => 'perm_post',     'label' => 'Post',                   'enabled' => true,  'required' => false],
                ['key' => 'perm_thana',    'label' => 'Thana',                  'enabled' => false, 'required' => false],
                ['key' => 'perm_district', 'label' => 'District',               'enabled' => true,  'required' => false],
                ['key' => 'perm_state',    'label' => 'State',                  'enabled' => true,  'required' => false],
                ['key' => 'perm_pincode',  'label' => 'Pin Code',               'enabled' => true,  'required' => false],
                ['key' => 'comm_address',  'label' => 'Communication Address',  'enabled' => true,  'required' => false],
            ],
        ],
        'education' => [
            'label' => 'Passed Exam Details',
            'icon'  => 'bi-mortarboard',
            'section_enabled' => true,
            'fields' => [
                ['key' => 'edu_10th',       'label' => '10th Details',          'enabled' => true,  'required' => false],
                ['key' => 'edu_12th',       'label' => '12th Details',          'enabled' => true,  'required' => false],
                ['key' => 'edu_graduation', 'label' => 'Graduation Details',    'enabled' => false, 'required' => false],
                ['key' => 'edu_other',      'label' => 'Other Exam',            'enabled' => false, 'required' => false],
            ],
        ],
    ];

    // Quick — section level toggle support
    private const QUICK_SECTIONS = [
        'office' => [
            'label' => 'Office Details',
            'icon'  => 'bi-briefcase',
            'section_enabled' => true,   // section toggle
            'fields' => [
                ['key' => 'form_no',            'label' => 'Serial No.',                'enabled' => true,  'required' => false, 'readonly' => true],
                ['key' => 'institute_form_no',  'label' => 'Form No.',                  'enabled' => false, 'required' => false],
                ['key' => 'sr_no',              'label' => 'Student Registration No.',  'enabled' => true,  'required' => false],
                ['key' => 'enrollment_no',      'label' => 'Enrollment No.',            'enabled' => false, 'required' => false],
                ['key' => 'roll_no',            'label' => 'Roll No.',                  'enabled' => false, 'required' => false],
                ['key' => 'exam_form_no',       'label' => 'Exam Form No.',             'enabled' => false, 'required' => false],
                ['key' => 'uin_no',             'label' => 'UIN No.',                   'enabled' => false, 'required' => false],
                ['key' => 'reference_no',       'label' => 'Reference No.',             'enabled' => false, 'required' => false],
                ['key' => 'admission_type',     'label' => 'Admission Type',            'enabled' => true,  'required' => false],
                ['key' => 'admission_source',   'label' => 'Admission Source',          'enabled' => true,  'required' => false],
                ['key' => 'gap_year',           'label' => 'Gap Year',                  'enabled' => true,  'required' => false],
                ['key' => 'admission_date',     'label' => 'Admission Date',            'enabled' => true,  'required' => false],
                ['key' => 'submitted_date',     'label' => 'Submitted Date',            'enabled' => true,  'required' => false],
                ['key' => 'academic_session',   'label' => 'Academic Session',          'enabled' => true,  'required' => false, 'readonly' => true],
            ],
        ],
        'basic' => [
            'label'           => 'Basic Details',
            'icon'            => 'bi-person',
            'section_enabled' => true,   // section toggle
            'fields' => [
                ['key' => 'photo',            'label' => 'Photo Upload',        'enabled' => true,  'required' => false],
                ['key' => 'name',             'label' => 'Student Name',        'enabled' => true,  'required' => true],
                ['key' => 'father_name',      'label' => 'Father Name',         'enabled' => true,  'required' => false],
                ['key' => 'father_mobile',    'label' => 'Father Mobile',       'enabled' => false, 'required' => false],
                ['key' => 'mother_name',      'label' => 'Mother Name',         'enabled' => true,  'required' => false],
                ['key' => 'dob',              'label' => 'Date of Birth',       'enabled' => true,  'required' => false],
                ['key' => 'gender',           'label' => 'Gender',              'enabled' => true,  'required' => false],
                ['key' => 'mobile',           'label' => 'Mobile No.',          'enabled' => true,  'required' => true],
                ['key' => 'email',            'label' => 'Email Address',       'enabled' => true,  'required' => false],
                ['key' => 'guardian_mobile',   'label' => 'Guardian Mobile',     'enabled' => false, 'required' => false],
                ['key' => 'guardian_name',     'label' => 'Guardian Name',       'enabled' => false, 'required' => false],
                ['key' => 'guardian_relation', 'label' => 'Guardian Relation',   'enabled' => false, 'required' => false],
                ['key' => 'religion',          'label' => 'Religion',            'enabled' => true,  'required' => false],
                ['key' => 'category',          'label' => 'Category',            'enabled' => true,  'required' => false],
                ['key' => 'special_category',  'label' => 'Special Category',    'enabled' => false, 'required' => false],
                ['key' => 'nationality',       'label' => 'Nationality',         'enabled' => false, 'required' => false],
                ['key' => 'aadhar_no',         'label' => 'Aadhar Card No.',     'enabled' => true,  'required' => false],
                ['key' => 'apaar_no',          'label' => 'APAAR No.',           'enabled' => false, 'required' => false],
                ['key' => 'student_type',      'label' => 'Student Type',        'enabled' => true,  'required' => false],
                ['key' => 'marital_status',    'label' => 'Marital Status',      'enabled' => false, 'required' => false],
            ],
        ],
        'address' => [
            'label' => 'Address Details',
            'icon'  => 'bi-geo-alt',
            'section_enabled' => false,  // default off
            'fields' => [
                ['key' => 'perm_village',  'label' => 'Village/City',           'enabled' => true,  'required' => false],
                ['key' => 'perm_post',     'label' => 'Post',                   'enabled' => true,  'required' => false],
                ['key' => 'perm_thana',    'label' => 'Thana',                  'enabled' => false, 'required' => false],
                ['key' => 'perm_district', 'label' => 'District',               'enabled' => true,  'required' => false],
                ['key' => 'perm_state',    'label' => 'State',                  'enabled' => true,  'required' => false],
                ['key' => 'perm_pincode',  'label' => 'Pin Code',               'enabled' => true,  'required' => false],
                ['key' => 'comm_address',  'label' => 'Communication Address',  'enabled' => true,  'required' => false],
            ],
        ],    
        'education' => [
            'label'           => 'Education Details',
            'icon'            => 'bi-mortarboard',
            'section_enabled' => true,
            'fields' => [
                ['key' => 'q_edu_10th',       'label' => '10th Details',       'enabled' => true,  'required' => false],
                ['key' => 'q_edu_12th',       'label' => '12th Details',       'enabled' => true,  'required' => false],
                ['key' => 'q_edu_graduation', 'label' => 'Graduation Details', 'enabled' => false, 'required' => false],
                ['key' => 'q_edu_other',      'label' => 'Other Exam',         'enabled' => false, 'required' => false],
            ],
        ],
    ];

    // Online — same as Admission (student fill kare)
    // Uses ADMISSION_SECTIONS but with online context

    // Fee Receipt — layout config
    private const RECEIPT_SECTIONS = [
        'layout' => [
            'label' => 'Receipt Layout',
            'icon'  => 'bi-layout-text-window',
            'fields' => [
                ['key' => 'show_logo',         'label' => 'Show Institute Logo',    'enabled' => true,  'required' => false],
                ['key' => 'show_student_photo', 'label' => 'Show Student Photo',    'enabled' => false, 'required' => false],
                ['key' => 'show_sign_line',    'label' => 'Show Signature Line',    'enabled' => true,  'required' => false],
                ['key' => 'show_watermark',    'label' => 'Show Watermark',         'enabled' => false, 'required' => false],
                ['key' => 'show_qr_code',      'label' => 'Show QR Code',           'enabled' => false, 'required' => false],
            ],
        ],
        'fields' => [
            'label' => 'Receipt Fields',
            'icon'  => 'bi-card-list',
            'fields' => [
                ['key' => 'receipt_student_id',  'label' => 'Student ID',           'enabled' => true,  'required' => false],
                ['key' => 'receipt_course',      'label' => 'Course/Stream',        'enabled' => true,  'required' => false],
                ['key' => 'receipt_father',      'label' => "Father's Name",        'enabled' => true,  'required' => false],
                ['key' => 'receipt_mobile',      'label' => 'Mobile No.',           'enabled' => true,  'required' => false],
                ['key' => 'receipt_address',     'label' => 'Address',              'enabled' => false, 'required' => false],
                ['key' => 'receipt_collected_by','label' => 'Collected By',         'enabled' => true,  'required' => false],
                ['key' => 'receipt_footer_note', 'label' => 'Footer Note',          'enabled' => true,  'required' => false],
            ],
        ],
    ];

    // ─── Map type → sections ───────────────────────────────────────────────
    private function getSectionsForType(string $type): array
    {
        return match($type) {
            'admission' => self::ADMISSION_SECTIONS,
            'quick'     => self::QUICK_SECTIONS,
            'online'    => self::ADMISSION_SECTIONS, // same fields, different flow
            'receipt'   => self::RECEIPT_SECTIONS,
            default     => self::ADMISSION_SECTIONS,
        };
    }

    // ─── Index ─────────────────────────────────────────────────────────────
    public function index()
    {
        $formTypes = self::FORM_TYPES;
        return view('institute.master.forms.index', compact('formTypes'));
    }

    // ─── Builder ───────────────────────────────────────────────────────────
    public function builder(string $type)
    {
        abort_if(!array_key_exists($type, self::FORM_TYPES), 404);

        $instituteId = auth()->user()->institute_id;
        $formInfo    = self::FORM_TYPES[$type];
        $sections    = $this->getSectionsForType($type);

        // Load saved config and merge
        $setting = AdmissionFormSetting::where('institute_id', $instituteId)
            ->where('form_type', $type)->first();

        if ($setting && $setting->field_config) {
            $savedList = json_decode($setting->field_config, true);
            $savedMap  = collect($savedList)->keyBy('key');

            // Section-level enabled restore — pehle ek field se section_enabled lo
            $sectionEnabledMap = [];
            foreach ($savedList as $savedField) {
                $sec = $savedField['section'] ?? null;
                if ($sec && isset($savedField['section_enabled'])) {
                    // Last value wins (all fields in same section have same value)
                    $sectionEnabledMap[$sec] = (bool) $savedField['section_enabled'];
                }
            }

            foreach ($sections as $sKey => &$section) {
                // section_enabled restore karo agar saved hai
                if (isset($sectionEnabledMap[$sKey])) {
                    $section['section_enabled'] = $sectionEnabledMap[$sKey];
                }
                foreach ($section['fields'] as &$field) {
                    if ($savedMap->has($field['key'])) {
                        $saved = $savedMap[$field['key']];
                        $field['enabled']  = $saved['enabled']  ?? $field['enabled'];
                        $field['required'] = $saved['required'] ?? $field['required'];
                    }
                }
            }
            unset($section, $field);
        }

        $currentFormConfig = self::getFormConfig($instituteId, $type);

        return view('institute.master.forms.builder',
            compact('type', 'formInfo', 'sections', 'currentFormConfig'));
    }

    // ─── Save ──────────────────────────────────────────────────────────────
    public function save(Request $request, string $type)
    {
        abort_if(!array_key_exists($type, self::FORM_TYPES), 404);

        $sections    = $this->getSectionsForType($type);
        $fieldConfig = [];

        foreach ($sections as $sKey => $section) {
            $hasSectionToggle = array_key_exists('section_enabled', $section);
            $sectionEnabled = $hasSectionToggle
                ? array_key_exists($sKey, (array) $request->input('section_enabled', []))
                : ($section['section_enabled'] ?? true);

            foreach ($section['fields'] as $field) {
                $key = $field['key'];
                $fieldConfig[] = [
                    'key'             => $key,
                    'section'         => $sKey,
                    'section_enabled' => $sectionEnabled,
                    'enabled'         => (bool) $request->input("fields.{$key}.enabled", false),
                    'required'        => (bool) $request->input("fields.{$key}.required", false),
                ];
            }
        }

        // form_config: collect_fee checkbox — absent means false
        $formConfigData = $request->input('form_config', []);
        if ($type === 'quick') {
            $formConfigData['collect_fee'] = $request->has('form_config.collect_fee') ? true : false;
        }

        AdmissionFormSetting::updateOrCreate(
            ['institute_id' => auth()->user()->institute_id, 'form_type' => $type],
            [
                'field_config' => json_encode($fieldConfig),
                'form_config'  => json_encode($formConfigData),
                'is_active'    => true,
            ]
        );

        return back()->with('success', self::FORM_TYPES[$type]['label'] . ' settings saved!');
    }

    // ─── Static helpers (Admission form me use hoga) ───────────────────────
    public static function getActiveConfig(int $instituteId, string $type = 'admission'): array
    {
        $controller = new self();
        $sections   = $controller->getSectionsForType($type);

        // Build default config
        $defaultConfig = [];
        foreach ($sections as $sKey => $section) {
            $sectionDefault = $section['section_enabled'] ?? true;
            foreach ($section['fields'] as $field) {
                $defaultConfig[$field['key']] = [
                    'enabled'         => $field['enabled'],
                    'required'        => $field['required'],
                    'section'         => $sKey,
                    'section_enabled' => $sectionDefault,
                ];
            }
        }

        // Merge with saved
        $setting = AdmissionFormSetting::where('institute_id', $instituteId)
            ->where('form_type', $type)->first();

        if (!$setting || !$setting->field_config) {
            return $defaultConfig;
        }

        foreach (json_decode($setting->field_config, true) as $field) {
            $defaultConfig[$field['key']] = [
                'enabled'         => (bool) $field['enabled'],
                'required'        => (bool) $field['required'],
                'section'         => $field['section'] ?? '',
                'section_enabled' => isset($field['section_enabled'])
                    ? (bool) $field['section_enabled']
                    : ($defaultConfig[$field['key']]['section_enabled'] ?? true),
            ];
        }

        return $defaultConfig;
    }

    public static function getSections(string $type = 'admission'): array
    {
        $controller = new self();
        return $controller->getSectionsForType($type);
    }

    // ─── form_config fetch (eg collect_fee flag for quick form) ───────────
    public static function getFormConfig(int $instituteId, string $type): array
    {
        $setting = AdmissionFormSetting::where('institute_id', $instituteId)
            ->where('form_type', $type)->first();

        if (!$setting || !$setting->form_config) {
            return [];
        }

        return json_decode($setting->form_config, true) ?: [];
    }
}
