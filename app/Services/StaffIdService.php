<?php

namespace App\Services;

use App\Models\Center;
use App\Models\CenterIdCounter;
use App\Models\ChannelPartner;
use App\Models\Institute;
use App\Models\LibraryStaff;
use App\Models\LibraryStaffIdCounter;
use App\Models\PartnerIdCounter;
use App\Models\StaffIdCounter;
use App\Models\StaffMember;
use Illuminate\Support\Facades\DB;
use Throwable;

class StaffIdService
{
    /**
     * Login-ID generator for Staff/Partner/Center/Library-Staff — mirrors
     * StudentIdService's counter-table + lockForUpdate() pattern exactly.
     */
    private static function shortName(int $instituteId): string
    {
        return strtoupper(Institute::find($instituteId)?->short_name ?? 'GEN');
    }

    private static function nextSeq(string $counterModel, int $instituteId, int $year): int
    {
        return DB::transaction(function () use ($counterModel, $instituteId, $year) {
            $counter = $counterModel::lockForUpdate()
                ->where('institute_id', $instituteId)
                ->where('year', $year)
                ->first();

            if (!$counter) {
                $counterModel::create([
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
    }

    // Format: BBA/STF/2026/0001
    public static function generateStaffId(int $instituteId, int $year): string
    {
        $seq = self::nextSeq(StaffIdCounter::class, $instituteId, $year);
        return self::shortName($instituteId) . '/STF/' . $year . '/' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // Format: BBA/PTR/2026/0001
    public static function generatePartnerId(int $instituteId, int $year): string
    {
        $seq = self::nextSeq(PartnerIdCounter::class, $instituteId, $year);
        return self::shortName($instituteId) . '/PTR/' . $year . '/' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // Format: BBA/CTR/2026/0001
    public static function generateCenterId(int $instituteId, int $year): string
    {
        $seq = self::nextSeq(CenterIdCounter::class, $instituteId, $year);
        return self::shortName($instituteId) . '/CTR/' . $year . '/' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // Format: BBA/LIB/2026/0001
    public static function generateLibraryStaffId(int $instituteId, int $year): string
    {
        $seq = self::nextSeq(LibraryStaffIdCounter::class, $instituteId, $year);
        return self::shortName($instituteId) . '/LIB/' . $year . '/' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Assigns UIDs to every existing row still missing one, using each row's
     * original created_at year. Idempotent — only ever touches NULL rows,
     * safe to re-run.
     */
    public static function backfillMissingUids(): array
    {
        $results = ['staff' => 0, 'partner' => 0, 'center' => 0, 'library_staff' => 0, 'errors' => []];

        $jobs = [
            'staff'         => [StaffMember::class, 'staff_uid', fn ($id, $year) => self::generateStaffId($id, $year)],
            'partner'       => [ChannelPartner::class, 'partner_uid', fn ($id, $year) => self::generatePartnerId($id, $year)],
            'center'        => [Center::class, 'center_uid', fn ($id, $year) => self::generateCenterId($id, $year)],
            'library_staff' => [LibraryStaff::class, 'employee_id', fn ($id, $year) => self::generateLibraryStaffId($id, $year)],
        ];

        foreach ($jobs as $key => [$modelClass, $column, $generator]) {
            $modelClass::whereNull($column)->orderBy('id')->get()->each(function ($row) use (&$results, $key, $column, $generator) {
                try {
                    $year = $row->created_at ? (int) $row->created_at->year : (int) date('Y');
                    $row->update([$column => $generator((int) $row->institute_id, $year)]);
                    $results[$key]++;
                } catch (Throwable $e) {
                    $results['errors'][] = get_class($row) . "#{$row->id}: " . $e->getMessage();
                }
            });
        }

        return $results;
    }

    public static function missingUidCounts(): array
    {
        return [
            'staff'         => StaffMember::whereNull('staff_uid')->count(),
            'partner'       => ChannelPartner::whereNull('partner_uid')->count(),
            'center'        => Center::whereNull('center_uid')->count(),
            'library_staff' => LibraryStaff::whereNull('employee_id')->count(),
        ];
    }
}
