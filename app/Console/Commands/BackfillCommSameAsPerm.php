<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;

class BackfillCommSameAsPerm extends Command
{
    protected $signature   = 'students:backfill-comm-same-as-perm {--dry-run : Sirf report karo, DB update mat karo}';
    protected $description = 'Un students ka comm_same_as_perm=false karo jinka communication address DB me already permanent se alag save hai, lekin flag purane bug ki wajah se stale true reh gaya tha';

    public function handle(): void
    {
        $dryRun  = (bool) $this->option('dry-run');
        $updated = 0;

        Student::where('comm_same_as_perm', true)
            ->where(function ($q) {
                $q->whereNotNull('comm_city')
                    ->orWhereNotNull('comm_thana')
                    ->orWhereNotNull('comm_post')
                    ->orWhereNotNull('comm_district')
                    ->orWhereNotNull('comm_state')
                    ->orWhereNotNull('comm_pincode');
            })
            ->chunkById(200, function ($students) use (&$updated, $dryRun) {
                foreach ($students as $student) {
                    $differs = $student->comm_city !== $student->perm_village
                        || $student->comm_thana !== $student->perm_thana
                        || $student->comm_post !== $student->perm_post
                        || $student->comm_district !== $student->perm_district
                        || $student->comm_state !== $student->perm_state
                        || $student->comm_pincode !== $student->perm_pincode;

                    if (!$differs) {
                        continue;
                    }

                    $this->line("Student #{$student->id} ({$student->name}): comm_same_as_perm true -> false");

                    if (!$dryRun) {
                        $student->update(['comm_same_as_perm' => false]);
                    }

                    $updated++;
                }
            });

        $label = $dryRun ? 'Would update' : 'Updated';
        $this->info("Done. {$label}: {$updated} student(s).");
    }
}
