<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\ChannelPartner;
use App\Models\StaffMember;
use App\Services\AuditLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class OtpMonitorController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index()
    {
        $pending = collect()
            ->concat($this->pendingFor(StaffMember::class, 'staff_login_otp', 'staff'))
            ->concat($this->pendingFor(Center::class, 'center_login_otp', 'center'))
            ->concat($this->pendingFor(ChannelPartner::class, 'partner_login_otp', 'channel partner'))
            ->sortByDesc('sent_at')
            ->values();

        AuditLogService::log($this->instituteId(), 'security', 'otp_monitor_viewed', 'Admin viewed live login OTPs.');

        return view('institute.master.otp-monitor.index', compact('pending'));
    }

    private function pendingFor(string $modelClass, string $cachePrefix, string $typeLabel)
    {
        return $modelClass::where('institute_id', $this->instituteId())
            ->where('status', true)
            ->get(['id', 'name', 'email'])
            ->map(function ($user) use ($cachePrefix, $typeLabel) {
                $payload = Cache::get("{$cachePrefix}:{$user->id}");

                if (!$payload || empty($payload['otp'])) {
                    return null;
                }

                return [
                    'type'    => $typeLabel,
                    'name'    => $user->name,
                    'email'   => $user->email,
                    'otp'     => $payload['otp'],
                    'sent_at' => isset($payload['sent_at']) ? Carbon::parse($payload['sent_at']) : null,
                ];
            })
            ->filter()
            ->values();
    }
}
