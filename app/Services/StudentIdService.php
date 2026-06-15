<?php

namespace App\Services;

use App\Models\AdmissionCounter;
use App\Models\FeeInvoiceCounter;
use App\Models\Institute;
use Illuminate\Support\Facades\DB;

class StudentIdService
{
    /**
     * Generate unique Student ID — conflict free
     * Format: BBA/STU/2026/0001
     *
     * Uses DB transaction + lockForUpdate to prevent race condition
     * when multiple staff submit admissions simultaneously.
     */
    public static function generateStudentId(int $instituteId, int $year): string
    {
        $shortName = Institute::find($instituteId)?->short_name ?? 'STU';
        $shortName = strtoupper($shortName);

        $seq = DB::transaction(function () use ($instituteId, $year) {
            // lockForUpdate — doosra request wait karega jab tak yeh complete na ho
            $counter = AdmissionCounter::lockForUpdate()
                ->where('institute_id', $instituteId)
                ->where('year', $year)
                ->first();

            if (!$counter) {
                // Pehla student is year ka
                $counter = AdmissionCounter::create([
                    'institute_id' => $instituteId,
                    'year'         => $year,
                    'last_seq'     => 1,
                ]);
                return 1;
            }

            $newSeq = $counter->last_seq + 1;
            $counter->update(['last_seq' => $newSeq]);
            return $newSeq;
        });

        // 6-digit zero-padded: 000001, 000002, ... 999999
        return $shortName . '/STU/' . $year . '/' . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Fee Invoice ID — conflict free
     * Format: BBA/FEE/2026/00001
     */
    public static function generateInvoiceId(int $instituteId, int $year): string
    {
        $shortName = Institute::find($instituteId)?->short_name ?? 'INV';
        $shortName = strtoupper($shortName);

        $seq = DB::transaction(function () use ($instituteId, $year) {
            $counter = FeeInvoiceCounter::lockForUpdate()
                ->where('institute_id', $instituteId)
                ->where('year', $year)
                ->first();

            if (!$counter) {
                FeeInvoiceCounter::create([
                    'institute_id' => $instituteId,
                    'year'         => $year,
                    'last_seq'     => 1,
                ]);
                return 1;
            }

            $newSeq = $counter->last_seq + 1;
            $counter->update(['last_seq' => $newSeq]);
            return $newSeq;
        });

        // 5-digit: 00001, 00002, ... 99999
        return $shortName . '/FEE/' . $year . '/' . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Academic session se year extract karo
     * "2025-26" → 2026
     * "2026-27" → 2027
     */
    public static function getYearFromSession(string $sessionName): int
    {
        // Format: "2025-26" ya "2025-2026"
        if (preg_match('/(\d{4})-(\d{2,4})$/', $sessionName, $m)) {
            $endPart = $m[2];
            if (strlen($endPart) === 2) {
                // "25-26" → 2026
                return (int) ('20' . $endPart);
            }
            return (int) $endPart;
        }
        return (int) date('Y');
    }
}
