<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Models\SmsProviderSetting;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsSettingController extends Controller
{
    private function instituteId(): int
    {
        return Auth::user()->institute_id;
    }

    public function index()
    {
        $instituteId = $this->instituteId();
        $setting     = SmsProviderSetting::where('institute_id', $instituteId)->first();

        $logsThisMonth = SmsLog::where('institute_id', $instituteId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->selectRaw('type, status, count(*) as total')
            ->groupBy('type', 'status')
            ->get();

        $recentLogs = SmsLog::where('institute_id', $instituteId)
            ->latest()
            ->limit(10)
            ->get();

        return view('institute.master.sms.index', compact('setting', 'logsThisMonth', 'recentLogs'));
    }

    public function save(Request $request)
    {
        $isCustom = $request->provider === 'custom';

        $request->validate([
            'provider'  => 'required|in:msg91,fast2sms,custom',
            'sender_id' => 'required|string|max:20',
            'api_key'   => $isCustom ? 'nullable|string|max:500' : 'nullable|string|max:500',
            // custom-specific
            'custom_endpoint'         => $isCustom ? 'required|url|max:500' : 'nullable',
            'custom_method'           => 'nullable|in:GET,POST',
            'custom_headers_json'     => 'nullable|string',
            'custom_body_template'    => 'nullable|string',
            'custom_success_key'      => 'nullable|string|max:100',
            'custom_success_value'    => 'nullable|string|max:100',
            'custom_credentials_json' => 'nullable|string',
        ]);

        $instituteId = $this->instituteId();
        $existing    = SmsProviderSetting::where('institute_id', $instituteId)->first();

        $data = [
            'provider'  => $request->provider,
            'sender_id' => strtoupper($request->sender_id),
            'is_active' => true,
        ];

        // API key: update only if provided; required for new non-custom records
        if ($request->filled('api_key')) {
            $data['api_key'] = $request->api_key;
        } elseif (! $existing && ! $isCustom) {
            return back()->withErrors(['api_key' => 'API Key is required for initial setup.'])->withInput();
        }

        if ($isCustom) {
            $data['custom_endpoint']      = $request->custom_endpoint;
            $data['custom_method']        = $request->custom_method ?? 'POST';
            $data['custom_headers_json']  = $request->custom_headers_json;
            $data['custom_body_template'] = $request->custom_body_template;
            $data['custom_success_key']   = $request->custom_success_key;
            $data['custom_success_value'] = $request->custom_success_value;
            if ($request->filled('custom_credentials_json')) {
                $data['custom_credentials_json'] = $request->custom_credentials_json;
            }
        }

        if ($existing) {
            $existing->fill($data)->save();
        } else {
            SmsProviderSetting::create(array_merge($data, ['institute_id' => $instituteId]));
        }

        return back()->with('success', 'SMS provider settings saved successfully.');
    }

    public function testConnection(Request $request)
    {
        if ($request->provider === 'custom') {
            $result = SmsService::testCustomEndpoint($request->custom_endpoint ?? '');
            return response()->json($result);
        }

        $request->validate([
            'provider'  => 'required|in:msg91,fast2sms',
            'api_key'   => 'required|string',
            'sender_id' => 'required|string',
        ]);

        $result = SmsService::testConnection(
            $request->provider,
            $request->api_key,
            $request->sender_id
        );

        return response()->json($result);
    }

    public function checkBalance()
    {
        $result = SmsService::checkInstituteBalance($this->instituteId());
        return response()->json($result);
    }

    public function logs(Request $request)
    {
        $instituteId = $this->instituteId();

        $query = SmsLog::where('institute_id', $instituteId)->latest();

        if ($request->filled('type') && in_array($request->type, ['otp', 'notice', 'due_reminder'])) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status') && in_array($request->status, ['pending', 'sent', 'failed'])) {
            $query->where('status', $request->status);
        }

        $logs    = $query->paginate(30)->withQueryString();
        $setting = SmsProviderSetting::where('institute_id', $instituteId)->first();

        return view('institute.master.sms.logs', compact('logs', 'setting'));
    }
}
