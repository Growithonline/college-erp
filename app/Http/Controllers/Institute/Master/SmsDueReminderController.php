<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\SmsDueReminderSetting;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsDueReminderController extends Controller
{
    private function instituteId(): int
    {
        return Auth::user()->institute_id;
    }

    public function index()
    {
        $instituteId = $this->instituteId();
        $setting     = SmsDueReminderSetting::where('institute_id', $instituteId)->first();
        $smsConfigured = SmsService::isInstituteConfigured($instituteId);

        return view('institute.master.sms.reminders', compact('setting', 'smsConfigured'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'trigger_days'     => 'required|array|min:1',
            'trigger_days.*'   => 'integer|min:0|max:60',
            'message_template' => 'required|string|max:500',
            'send_time'        => 'required|date_format:H:i',
        ]);

        $days = array_unique(array_map('intval', $request->trigger_days));
        sort($days);

        SmsDueReminderSetting::updateOrCreate(
            ['institute_id' => $this->instituteId()],
            [
                'trigger_days'     => implode(',', $days),
                'message_template' => $request->message_template,
                'send_time'        => $request->send_time . ':00',
            ]
        );

        return back()->with('success', 'Due reminder settings saved.');
    }

    public function toggle()
    {
        $setting = SmsDueReminderSetting::where('institute_id', $this->instituteId())->first();

        if (! $setting) {
            return back()->with('error', 'Save settings first before enabling.');
        }

        if (! SmsService::isInstituteConfigured($this->instituteId())) {
            return back()->with('error', 'Configure the SMS provider first.');
        }

        $setting->update(['is_enabled' => ! $setting->is_enabled]);
        $status = $setting->is_enabled ? 'enabled' : 'disabled';

        return back()->with('success', "Due reminders {$status}.");
    }
}
