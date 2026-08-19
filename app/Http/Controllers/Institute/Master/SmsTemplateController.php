<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\SmsBroadcast;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsTemplateController extends Controller
{
    // Types manageable here. OTP is deliberately excluded — its template/DLT id live on the
    // main SMS Settings page (per-institute otp_message_template/otp_id), not sms_templates.
    // is_multi = an institute can register several named templates for this type (content
    // shape varies too much per instance to force into one fixed template), vs. single-template
    // types where there's always exactly one row per institute+type.
    private const TYPES = [
        SmsTemplate::TYPE_FEE_DUE_REMINDER => [
            'label'    => 'Fee Due Reminder',
            'category' => SmsTemplate::CATEGORY_TRANSACTIONAL,
            'vars'     => ['name', 'amount', 'due_date', 'institute_name', 'course'],
            'wired'    => true,
            'is_multi' => false,
        ],
        SmsTemplate::TYPE_FEE_TXN_ALERT => [
            'label'    => 'Fee Transaction Alert',
            'category' => SmsTemplate::CATEGORY_TRANSACTIONAL,
            'vars'     => ['name', 'course', 'amount', 'payment_date', 'invoice_no'],
            'wired'    => true,
            'is_multi' => false,
        ],
        SmsTemplate::TYPE_ADMISSION_ALERT => [
            'label'    => 'Admission / Credentials',
            'category' => SmsTemplate::CATEGORY_TRANSACTIONAL,
            'vars'     => ['name', 'student_uid', 'password', 'login_url', 'institute_name'],
            'wired'    => true,
            'is_multi' => false,
        ],
        SmsTemplate::TYPE_NOTICE => [
            'label'    => 'Notice',
            'category' => SmsTemplate::CATEGORY_TRANSACTIONAL,
            'vars'     => [], // free-form — institute defines its own variable names per template
            'wired'    => true,
            'is_multi' => true,
        ],
        SmsTemplate::TYPE_EXAM_INFO => [
            'label'    => 'Exam Info',
            'category' => SmsTemplate::CATEGORY_TRANSACTIONAL,
            'vars'     => ['name', 'exam_name', 'date'],
            'wired'    => false,
            'is_multi' => false,
        ],
        SmsTemplate::TYPE_ADMIT_CARD => [
            'label'    => 'Admit Card',
            'category' => SmsTemplate::CATEGORY_TRANSACTIONAL,
            'vars'     => ['name', 'exam_name', 'date'],
            'wired'    => false,
            'is_multi' => false,
        ],
        SmsTemplate::TYPE_PROMOTION => [
            'label'    => 'Promotion',
            'category' => SmsTemplate::CATEGORY_PROMOTIONAL,
            'vars'     => ['name', 'message'],
            'wired'    => false,
            'is_multi' => false,
        ],
    ];

    private function instituteId(): int
    {
        return Auth::user()->institute_id;
    }

    public function index()
    {
        $instituteId   = $this->instituteId();
        $smsConfigured = SmsService::isInstituteConfigured($instituteId);

        $existing = SmsTemplate::where('institute_id', $instituteId)->get();

        $rows = [];
        foreach (self::TYPES as $type => $meta) {
            if ($meta['is_multi']) {
                $rows[$type] = [
                    'meta'      => $meta,
                    'templates' => $existing->where('type', $type)->values(),
                ];
            } else {
                $rows[$type] = [
                    'meta'     => $meta,
                    'template' => $existing->firstWhere('type', $type),
                ];
            }
        }

        return view('institute.master.sms.templates.index', compact('rows', 'smsConfigured'));
    }

    public function save(Request $request)
    {
        $type = $request->input('type');

        if (! array_key_exists($type, self::TYPES)) {
            return back()->with('error', 'Unknown template type.');
        }

        $meta    = self::TYPES[$type];
        $isMulti = $meta['is_multi'];

        $rules = [
            'category'        => 'required|in:transactional,promotional',
            'dlt_template_id' => 'nullable|string|max:100',
            'content'         => 'required|string|max:1000',
        ];
        if ($isMulti) {
            $rules['name'] = 'required|string|max:100';
        }
        $request->validate($rules);

        // Multi-template types (notice) use free-form variable names — whatever the institute
        // types — since content shape varies per template. Single-template types stay
        // restricted to their known variable set. Either way: only what's actually in the
        // content, in appearance order, repeats preserved — see save() history for why (Fast2SMS
        // positional variables_values needs an exact count match with the DLT-registered template).
        // Allows digits after the first char (e.g. {text_2}, {text_3}) — needed for the compose
        // page's auto-disambiguated duplicate-variable names (text, text_2, text_3, ...).
        preg_match_all('/\{([a-z_][a-z0-9_]*)\}/', $request->content, $matches);
        $usedVars = $isMulti
            ? array_values($matches[1])
            : array_values(array_intersect($matches[1], $meta['vars']));

        $data = [
            'category'        => $request->category,
            'dlt_template_id' => $request->dlt_template_id,
            'content'         => $request->content,
            'variable_names'  => json_encode($usedVars),
        ];

        if ($isMulti) {
            $templateId = $request->input('template_id');

            if ($templateId) {
                $template = SmsTemplate::where('institute_id', $this->instituteId())
                    ->where('type', $type)
                    ->where('id', $templateId)
                    ->first();

                if (! $template) {
                    return back()->with('error', 'Template not found.')->withInput();
                }

                $data['name'] = $request->name;
                $template->update($data);
            } else {
                $exists = SmsTemplate::where('institute_id', $this->instituteId())
                    ->where('type', $type)->where('name', $request->name)->exists();

                if ($exists) {
                    return back()->with('error', 'A template with this name already exists.')->withInput();
                }

                SmsTemplate::create(array_merge($data, [
                    'institute_id' => $this->instituteId(),
                    'type'         => $type,
                    'name'         => $request->name,
                ]));
            }
        } else {
            SmsTemplate::updateOrCreate(
                ['institute_id' => $this->instituteId(), 'type' => $type],
                $data
            );
        }

        return back()->with('success', $meta['label'] . ' template saved.');
    }

    public function toggle(Request $request)
    {
        $type  = $request->input('type');
        $query = SmsTemplate::where('institute_id', $this->instituteId())->where('type', $type);

        if ($request->filled('template_id')) {
            $query->where('id', $request->template_id);
        }

        $template = $query->first();

        if (! $template) {
            return back()->with('error', 'Save the template first before enabling.');
        }

        $template->update(['is_active' => ! $template->is_active]);

        return back()->with('success', ($template->is_active ? 'Enabled.' : 'Disabled.'));
    }

    public function destroy(Request $request)
    {
        $template = SmsTemplate::where('institute_id', $this->instituteId())
            ->where('id', $request->input('template_id'))
            ->where('type', SmsTemplate::TYPE_NOTICE)
            ->first();

        if (! $template) {
            return back()->with('error', 'Template not found.');
        }

        if (SmsBroadcast::where('sms_template_id', $template->id)->exists()) {
            return back()->with('error', 'Cannot delete — this template is used by existing SMS broadcasts.');
        }

        $template->delete();

        return back()->with('success', 'Template deleted.');
    }
}
