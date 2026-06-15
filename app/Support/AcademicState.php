<?php

namespace App\Support;

class AcademicState
{
    private const SEMESTERS_PER_YEAR = 2;

    public static function yearNumber(?string $structureType, ?int $semester, ?int $fallbackYearNumber = null): ?int
    {
        $structureType = strtolower((string) $structureType);

        if ($structureType === 'semester' && $semester) {
            return max(1, (int) ceil($semester / self::SEMESTERS_PER_YEAR));
        }

        if ($fallbackYearNumber !== null && $fallbackYearNumber > 0) {
            return (int) $fallbackYearNumber;
        }

        return $semester ? max(1, (int) ceil($semester / self::SEMESTERS_PER_YEAR)) : null;
    }

    public static function yearLabel(?string $structureType, ?int $semester, ?int $fallbackYearNumber = null): string
    {
        $yearNumber = self::yearNumber($structureType, $semester, $fallbackYearNumber);

        if (!$yearNumber) {
            return '—';
        }

        return self::ordinalYearLabel($yearNumber);
    }

    public static function ordinalYearLabel(int $yearNumber): string
    {
        $yearNumber = max(1, $yearNumber);

        $suffix = match (true) {
            $yearNumber % 100 >= 11 && $yearNumber % 100 <= 13 => 'th',
            $yearNumber % 10 === 1 => 'st',
            $yearNumber % 10 === 2 => 'nd',
            $yearNumber % 10 === 3 => 'rd',
            default => 'th',
        };

        return "{$yearNumber}{$suffix} Year";
    }
}
