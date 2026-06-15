<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Jobs\SendBroadcastSmsJob;
use App\Models\Institute;
use App\Models\PlatformSmsSetting;
use App\Models\SmsLog;
use App\Models\SmsProviderSetting;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SmsSettingController extends Controller
{
    public function index()
    {
        $settings = PlatformSmsSetting::current();

        $institutes = Institute::where('status', 'active')->orderBy('name')->get();

        $smsSettingsAll = SmsProviderSetting::whereIn('institute_id', $institutes->pluck('id'))->get();
        $smsSettings    = $smsSettingsAll->keyBy('institute_id');

        $monthlyLogCounts = SmsLog::whereNotNull('institute_id')
            ->whereIn('institute_id', $institutes->pluck('id'))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->selectRaw('institute_id, COUNT(*) as cnt')
            ->groupBy('institute_id')
            ->pluck('cnt', 'institute_id');

        $lastUsedDates = SmsLog::whereNotNull('institute_id')
            ->whereIn('institute_id', $institutes->pluck('id'))
            ->selectRaw('institute_id, MAX(created_at) as last_used')
            ->groupBy('institute_id')
            ->pluck('last_used', 'institute_id');

        $instituteStats = $institutes->map(fn ($institute) => [
            'institute'       => $institute,
            'setting'         => $smsSettings->get($institute->id),
            'sent_this_month' => (int) $monthlyLogCounts->get($institute->id, 0),
            'last_used'       => $lastUsedDates->get($institute->id),
        ]);

        $platformOtpThisMonth = SmsLog::whereNull('institute_id')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalSmsThisMonth = SmsLog::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $configuredCount = $smsSettingsAll->where('is_active', true)->where('is_sms_disabled', false)->count();
        $disabledCount   = $smsSettingsAll->where('is_sms_disabled', true)->count();

        return view('super_admin.sms.index', compact(
            'settings', 'instituteStats', 'platformOtpThisMonth',
            'totalSmsThisMonth', 'configuredCount', 'disabledCount'
        ));
    }

    public function analytics()
    {
        $startDate = now()->subMonths(5)->startOfMonth();

        $monthlyCounts = SmsLog::where('created_at', '>=', $startDate)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, type, COUNT(*) as cnt")
            ->groupBy('month', 'type')
            ->get();

        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));

        $monthlyData = $months->map(function ($month) use ($monthlyCounts) {
            $key  = $month->format('Y-m');
            $rows = $monthlyCounts->where('month', $key);
            return [
                'label'        => $month->format('M Y'),
                'otp'          => (int) ($rows->firstWhere('type', 'otp')?->cnt ?? 0),
                'notice'       => (int) ($rows->firstWhere('type', 'notice')?->cnt ?? 0),
                'due_reminder' => (int) ($rows->firstWhere('type', 'due_reminder')?->cnt ?? 0),
            ];
        });

        $totals = SmsLog::selectRaw("
            COUNT(*) as grand_total,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as total_sent,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_failed,
            SUM(CASE WHEN institute_id IS NULL THEN 1 ELSE 0 END) as total_otp,
            SUM(CASE WHEN type = 'notice' THEN 1 ELSE 0 END) as total_notice,
            SUM(CASE WHEN type = 'due_reminder' THEN 1 ELSE 0 END) as total_due_reminder
        ")->first();

        $topRows = SmsLog::whereNotNull('institute_id')
            ->selectRaw("
                institute_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
                SUM(CASE WHEN type = 'notice' THEN 1 ELSE 0 END) as notice_count,
                SUM(CASE WHEN type = 'due_reminder' THEN 1 ELSE 0 END) as reminder_count,
                MAX(created_at) as last_at
            ")
            ->groupBy('institute_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $instituteNames = Institute::whereIn('id', $topRows->pluck('institute_id'))
            ->pluck('name', 'id');

        $topInstitutes = $topRows->map(fn ($row) => [
            'id'             => $row->institute_id,
            'name'           => $instituteNames->get($row->institute_id, 'Unknown'),
            'total'          => (int) $row->total,
            'sent_count'     => (int) $row->sent_count,
            'failed_count'   => (int) $row->failed_count,
            'notice_count'   => (int) $row->notice_count,
            'reminder_count' => (int) $row->reminder_count,
            'last_at'        => $row->last_at,
        ]);

        $otpThisMonth = SmsLog::whereNull('institute_id')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status='sent' THEN 1 ELSE 0 END) as sent")
            ->first();

        $otpLastMonth = SmsLog::whereNull('institute_id')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        return view('super_admin.sms.analytics', compact(
            'monthlyData', 'totals', 'topInstitutes', 'otpThisMonth', 'otpLastMonth'
        ));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'provider'                    => 'required|in:msg91,fast2sms,custom',
            'api_key'                     => 'nullable|string|max:500',
            'sender_id'                   => 'nullable|string|max:20',
            'otp_expiry_minutes'          => 'required|integer|min:1|max:60',
            'otp_max_attempts'            => 'required|integer|min:1|max:10',
            'otp_resend_cooldown_seconds' => 'required|integer|min:10|max:300',
            'custom_endpoint'             => 'required_if:provider,custom|nullable|url|max:500',
            'custom_method'               => 'nullable|in:GET,POST',
            'custom_headers_json'         => 'nullable|string|max:1000',
            'custom_body_template'        => 'nullable|string|max:2000',
            'custom_success_key'          => 'nullable|string|max:100',
            'custom_success_value'        => 'nullable|string|max:100',
            'custom_credentials_json'     => 'nullable|string|max:2000',
            'otp_message_template'        => 'nullable|string|max:500',
        ]);

        $settings                              = PlatformSmsSetting::firstOrNew([]);
        $settings->provider                    = $request->provider;
        $settings->sender_id                   = $request->filled('sender_id') ? strtoupper($request->sender_id) : ($settings->sender_id ?? '');
        $settings->otp_expiry_minutes          = $request->otp_expiry_minutes;
        $settings->otp_max_attempts            = $request->otp_max_attempts;
        $settings->otp_resend_cooldown_seconds = $request->otp_resend_cooldown_seconds;

        if ($request->filled('api_key')) {
            $settings->api_key = $request->api_key;
        }

        if ($request->filled('otp_message_template')) {
            $settings->otp_message_template = $request->otp_message_template;
        }

        if ($request->provider === 'custom') {
            $settings->custom_endpoint      = $request->custom_endpoint;
            $settings->custom_method        = $request->custom_method ?? 'POST';
            $settings->custom_headers_json  = $request->custom_headers_json;
            $settings->custom_body_template = $request->custom_body_template;
            $settings->custom_success_key   = $request->custom_success_key;
            $settings->custom_success_value = $request->custom_success_value;
            if ($request->filled('custom_credentials_json')) {
                $settings->custom_credentials_json = $request->custom_credentials_json;
            }
        }

        $settings->save();

        return back()->with('success', 'Platform SMS settings saved successfully.');
    }

    public function toggleActive(Request $request)
    {
        $settings = PlatformSmsSetting::current();

        if (! $settings) {
            return back()->with('error', 'Configure SMS settings first.');
        }

        $settings->update(['is_active' => ! $settings->is_active]);

        $status = $settings->is_active ? 'enabled' : 'disabled';
        return back()->with('success', "Platform OTP SMS {$status}.");
    }

    public function testConnection(Request $request)
    {
        if ($request->input('provider') === 'custom') {
            $request->validate(['custom_endpoint' => 'required|url']);
            return response()->json(SmsService::testCustomEndpoint($request->input('custom_endpoint')));
        }

        $request->validate([
            'provider'  => 'required|in:msg91,fast2sms',
            'api_key'   => 'required|string',
            'sender_id' => 'required|string',
        ]);

        return response()->json(SmsService::testConnection(
            $request->input('provider'),
            $request->input('api_key'),
            $request->input('sender_id')
        ));
    }

    public function toggleInstituteSmS(Request $request, int $instituteId)
    {
        $setting = SmsProviderSetting::where('institute_id', $instituteId)->firstOrFail();
        $setting->update(['is_sms_disabled' => ! $setting->is_sms_disabled]);

        $status = $setting->is_sms_disabled ? 'disabled' : 'enabled';
        return back()->with('success', "SMS {$status} for {$setting->institute->name}.");
    }

    public function showBroadcast()
    {
        $institutes = Institute::where('status', 'active')
            ->whereNotNull('owner_mobile')
            ->orderBy('name')
            ->get(['id', 'name', 'owner_name', 'owner_mobile', 'institute_uid', 'subscription_end']);

        $platformConfigured = SmsService::isPlatformConfigured();

        $recentBroadcasts = SmsLog::whereIn('type', [
                SmsLog::TYPE_BROADCAST,
                SmsLog::TYPE_WELCOME,
                'subscription_expiry',
                'payment_reminder',
            ])
            ->with('institute:id,name')
            ->latest()
            ->limit(20)
            ->get();

        return view('super_admin.sms.broadcast', compact('institutes', 'platformConfigured', 'recentBroadcasts'));
    }

    public function sendBroadcast(Request $request)
    {
        $request->validate([
            'target'       => 'required|in:all,single',
            'institute_id' => 'required_if:target,single|nullable|exists:institutes,id',
            'message'      => 'required|string|max:500',
            'type'         => 'required|in:broadcast,subscription_expiry,payment_reminder',
        ]);

        if (! SmsService::isPlatformConfigured()) {
            return back()->with('error', 'Platform SMS configured nahi hai. Pehle SMS Settings mein provider set karo.');
        }

        if ($request->target === 'all') {
            $institutes = Institute::where('status', 'active')
                ->whereNotNull('owner_mobile')
                ->pluck('id');

            foreach ($institutes as $id) {
                SendBroadcastSmsJob::dispatch($id, $request->message, $request->type);
            }

            return back()->with('success', "{$institutes->count()} institutes ko SMS queue mein add kar diya.");
        }

        SendBroadcastSmsJob::dispatch(
            (int) $request->institute_id,
            $request->message,
            $request->type
        );

        return back()->with('success', 'SMS queue mein add kar diya gaya.');
    }

    public function instituteLogs(Request $request, int $instituteId)
    {
        $setting = SmsProviderSetting::with('institute:id,name')
            ->where('institute_id', $instituteId)
            ->firstOrFail();

        $query = SmsLog::where('institute_id', $instituteId)->latest();

        if ($request->filled('type') && in_array($request->type, ['otp', 'notice', 'due_reminder'])) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status') && in_array($request->status, ['pending', 'sent', 'failed'])) {
            $query->where('status', $request->status);
        }
        if ($request->filled('month')) {
            $parts = explode('-', $request->month, 2);
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                $query->whereYear('created_at', $parts[0])->whereMonth('created_at', $parts[1]);
            }
        }

        $logs = $query->paginate(50)->withQueryString();

        $stats = SmsLog::where('institute_id', $instituteId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN type = 'notice' THEN 1 ELSE 0 END) as notices,
                SUM(CASE WHEN type = 'due_reminder' THEN 1 ELSE 0 END) as reminders
            ")
            ->first();

        return view('super_admin.sms.institute-logs', compact('logs', 'setting', 'stats'));
    }
}
