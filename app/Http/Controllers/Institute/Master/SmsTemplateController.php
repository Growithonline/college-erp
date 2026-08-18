<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsTemplateController extends Controller
{
    // Types manageable here. OTP is deliberately excluded — its template/DLT id live on the
    // main SMS Settings page (per-institute otp_message_template/otp_id), not sms_templates.
    private const TYPES = [
        SmsTemplate::TYPE_FEE_DUE_REMINDER => [
            'label'    => 'Fee Due Reminder',
            'category' => SmsTemplate::CATEGORY_TRANSACTIONAL,
            'vars'     => ['name', 'amount', 'due_date', 'institute_name', 'course'],
            'wired'    => true,
        ],
        SmsTemplate::TYPE_FEE_TXN_ALERT => [
            'label'    => 'Fee Transaction Alert',
            'category' => SmsTemplate::CATEGORY_TRANSACTIONAL,
            'vars'     => ['name', 'amount', 'invoice_no', 'payment_date'],
            'wired'    => true,
        ],
        SmsTemplate::TYPE_ADMISSION_ALERT => [
            'label'    => 'Admission / Credentials',
            'category' => SmsTemplate::CATEGORY_TRANSACTIONAL,
            'vars'     => ['name', 'student_uid', 'password', 'login_url', 'institute_name'],
            'wired'    => true,
        ],
        SmsTemplate::TYPE_NOTICE => [
            'label'    => 'Notice',
            'category' => SmsTemplate::CATEGORY_TRANSACTIONAL,
            'vars'     => ['type', 'title'],
            'wired'    => true,
        ],
        SmsTemplate::TYPE_EXAM_INFO => [
            'label'    => 'Exam Info',
            'category' => SmsTemplate::CATEGORY_TRANSACTIONAL,
            'vars'     => ['name', 'exam_name', 'date'],
            'wired'    => false,
        ],
        SmsTemplate::TYPE_ADMIT_CARD => [
            'label'    => 'Admit Card',
            'category' => SmsTemplate::CATEGORY_TRANSACTIONAL,
            'vars'     => ['name', 'exam_name', 'date'],
            'wired'    => false,
        ],
        SmsTemplate::TYPE_PROMOTION => [
            'label'    => 'Promotion',
            'category' => SmsTemplate::CATEGORY_PROMOTIONAL,
            'vars'     => ['name', 'message'],
            'wired'    => false,
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

        $existing = SmsTemplate::where('institute_id', $instituteId)
            ->get()
            ->keyBy('type');

        $rows = [];
        foreach (self::TYPES as $type => $meta) {
            $rows[$type] = [
                'meta'     => $meta,
                'template' => $existing->get($type),
            ];
        }

        return view('institute.master.sms.templates.index', compact('rows', 'smsConfigured'));
    }

    public function save(Request $request)
    {
        $type = $request->input('type');

        if (! array_key_exists($type, self::TYPES)) {
            return back()->with('error', 'Unknown template type.');
        }

        $request->validate([
            'category'        => 'required|in:transactional,promotional',
            'dlt_template_id' => 'nullable|string|max:100',
            'content'         => 'required|string|max:1000',
        ]);

        SmsTemplate::updateOrCreate(
            ['institute_id' => $this->instituteId(), 'type' => $type],
            [
                'category'        => $request->category,
                'dlt_template_id' => $request->dlt_template_id,
                'content'         => $request->content,
                'variable_names'  => json_encode(self::TYPES[$type]['vars']),
            ]
        );

        return back()->with('success', self::TYPES[$type]['label'] . ' template saved.');
    }

    public function toggle(Request $request)
    {
        $type     = $request->input('type');
        $template = SmsTemplate::where('institute_id', $this->instituteId())->where('type', $type)->first();

        if (! $template) {
            return back()->with('error', 'Save the template first before enabling.');
        }

        $template->update(['is_active' => ! $template->is_active]);

        return back()->with('success', ($template->is_active ? 'Enabled.' : 'Disabled.'));
    }
}
