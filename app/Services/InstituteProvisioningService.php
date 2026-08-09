<?php

namespace App\Services;

use App\Mail\InstituteCredentialMail;
use App\Models\Institute;
use App\Models\SmsLog;
use App\Models\User;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class InstituteProvisioningService
{
    /**
     * Creates an Institute + its owner login account, seeds default staff roles
     * and the accounting chart-of-accounts, then emails/SMSes the owner their
     * credentials. Shared by Super-Admin's and Group-Admin's creation flows.
     *
     * @param array $data StoreInstituteRequest::validated() shape, plus:
     *   - 'group_id' (nullable int) — null for Super-Admin-created institutes
     *   - 'student_limit' (int) — resolved by the caller
     *   - 'image' / 'owner_identity_proof' (UploadedFile|null)
     * @param Closure|null $beforeInsert Runs inside the DB transaction, right
     *   before UID generation — e.g. the Group-Admin flow uses this to row-lock
     *   the Group and race-safely enforce its institute quota. Throw to abort.
     */
    public function create(array $data, ?Closure $beforeInsert = null): Institute
    {
        DB::beginTransaction();

        try {
            if ($beforeInsert) {
                $beforeInsert();
            }

            $uid = $this->generateInstituteUID();

            $imagePath = ($data['image'] ?? null) instanceof UploadedFile
                ? $data['image']->store('institutes/images', 'public')
                : null;

            $identityProofPath = ($data['owner_identity_proof'] ?? null) instanceof UploadedFile
                ? $data['owner_identity_proof']->store('institutes/identity_proofs', 'public')
                : null;

            $institute = Institute::create([
                'institute_uid'        => $uid,
                'group_id'             => $data['group_id'] ?? null,
                'name'                 => $data['name'],
                'short_name'           => strtoupper($data['short_name']),
                'mobile'               => $data['mobile'],
                'email'                => $data['email'],
                'image'                => $imagePath,
                'primary_color'        => $data['primary_color'] ?? null,
                'address'              => $data['address'] ?? null,
                'city'                 => $data['city'] ?? null,
                'state'                => $data['state'] ?? null,
                'pincode'              => $data['pincode'] ?? null,
                'owner_name'           => $data['owner_name'],
                'owner_mobile'         => $data['owner_mobile'],
                'owner_email'          => $data['owner_email'],
                'owner_whatsapp'       => $data['owner_whatsapp'] ?? null,
                'owner_address'        => $data['owner_address'] ?? null,
                'owner_identity_proof' => $identityProofPath,
                'student_limit'        => $data['student_limit'],
                'subscription_start'   => $data['subscription_start'] ?? now(),
                'subscription_end'     => $data['subscription_end'] ?? null,
                'status'               => 'active',
            ]);

            $plainPassword = Str::random(10);

            $user = User::create([
                'institute_id' => $institute->id,
                'name'         => $data['owner_name'],
                'email'        => $data['owner_email'],
                'mobile'       => $data['owner_mobile'],
                'password'     => Hash::make($plainPassword),
            ]);
            // 'role' is not mass-assignable — set directly to prevent privilege escalation
            $user->role = 'institute_admin';
            $user->save();

            \Database\Seeders\StaffRoleSeeder::createDefaultRoles($institute->id);
            AccountingSetupService::bootstrapInstitute($institute->id);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // ── Email — completely outside DB transaction ────────────────
        try {
            Mail::mailer('smtp')->to($user->email)->send(new InstituteCredentialMail(
                ownerName:     $data['owner_name'],
                instituteName: $data['name'],
                instituteUid:  $uid,
                email:         $user->email,
                password:      $plainPassword,
                loginUrl:      url('/login'),
                logoUrl:       asset('images/logog.png'),
            ));
        } catch (Throwable $mailEx) {
            \Log::warning('Institute welcome email failed', [
                'institute_id' => $institute->id,
                'error'        => $mailEx->getMessage(),
            ]);
        }

        // ── SMS — fire-and-forget ────────────────────────────────────
        try {
            if (!empty($data['owner_mobile']) && SmsService::isPlatformConfigured()) {
                $smsMessage = "Welcome to College ERP! Institute ID: {$uid} | Email: {$user->email} | Password: {$plainPassword} | Login: " . url('/login') . " | Please change your password after first login.";
                SmsService::sendFromPlatform($data['owner_mobile'], $smsMessage, SmsLog::TYPE_WELCOME, $institute->id);
            }
        } catch (Throwable $smsEx) {
            \Log::warning('Institute welcome SMS failed', [
                'institute_id' => $institute->id,
                'error'        => $smsEx->getMessage(),
            ]);
        }

        return $institute;
    }

    private function generateInstituteUID(): string
    {
        $year = now()->year;
        $count = Institute::whereYear('created_at', $year)->lockForUpdate()->count() + 1;
        return 'GT/' . $year . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
