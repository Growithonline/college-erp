<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\InstituteMasterOtp;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Crypt;

class MasterOtpController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index()
    {
        $masterOtp = InstituteMasterOtp::with('generatedBy')
            ->where('institute_id', $this->instituteId())
            ->first();

        return view('institute.master.master-otp.index', compact('masterOtp'));
    }

    public function generate()
    {
        $otp = (string) random_int(100000, 999999);

        $masterOtp = InstituteMasterOtp::updateOrCreate(
            ['institute_id' => $this->instituteId()],
            [
                'otp_encrypted' => Crypt::encryptString($otp),
                'valid_month'   => now()->format('Y-m'),
                'generated_at'  => now(),
                'generated_by'  => auth()->id(),
            ]
        );

        AuditLogService::log($this->instituteId(), 'security', 'master_otp_generated', 'Institute master OTP generated/reset.', $masterOtp);

        return redirect()->route('master.master-otp.index')
            ->with('success', 'Master OTP generated!');
    }

    public function reveal()
    {
        $masterOtp = InstituteMasterOtp::where('institute_id', $this->instituteId())->first();

        abort_if(!$masterOtp || !$masterOtp->isValidNow(), 404, 'No active Master OTP for this month.');

        AuditLogService::log($this->instituteId(), 'security', 'master_otp_revealed', 'Admin revealed the master OTP.', $masterOtp);

        return response()->json(['otp' => $masterOtp->decryptOtp()]);
    }
}
