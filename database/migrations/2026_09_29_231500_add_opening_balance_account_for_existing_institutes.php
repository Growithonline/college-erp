<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Backfills the new "Opening Balance — Migrated Fees" account (code 5000, added to
    // AccountingSetupService::defaultAccountDefinitions() alongside this migration) for
    // every institute whose chart of accounts was already bootstrapped BEFORE this
    // change. A brand new institute gets it automatically the normal way, through
    // AccountingSetupService::bootstrapInstitute(). This only inserts the ONE missing
    // account row per institute (never touches any existing account), so nothing an
    // institute already has configured is altered.
    public function up(): void
    {
        $now = now();

        $instituteIds = DB::table('accounts')->distinct()->pluck('institute_id');
        $existing = DB::table('accounts')->where('code', '5000')->pluck('institute_id')->flip();

        $rows = [];
        foreach ($instituteIds as $instituteId) {
            if (isset($existing[$instituteId])) {
                continue;
            }
            $rows[] = [
                'institute_id'          => $instituteId,
                'parent_id'             => null,
                'code'                  => '5000',
                'name'                  => 'Opening Balance — Migrated Fees',
                'type'                  => 'equity',
                'normal_side'           => 'credit',
                'linked_type'           => null,
                'linked_id'             => null,
                'meta'                  => null,
                'is_system'             => true,
                'is_active'             => true,
                'allow_manual_posting'  => true,
                'created_at'            => $now,
                'updated_at'            => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('accounts')->insert($chunk);
        }
    }

    public function down(): void
    {
        DB::table('accounts')->where('code', '5000')->where('is_system', true)->delete();
    }
};
