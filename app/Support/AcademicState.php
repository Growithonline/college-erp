<?php

namespace App\Support;

class AcademicState
{
    public static function yearNumber(?string $structureType, ?int $semester, ?int $fallbackYearNumber = null, int $semestersPerYear = 2): ?int
    {
        $structureType   = strtolower((string) $structureType);
        $spy             = max(1, $semestersPerYear);

        if (in_array($structureType, ['semester', 'trimester']) && $semester) {
            return max(1, (int) ceil($semester / $spy));
        }

        if ($fallbackYearNumber !== null && $fallbackYearNumber > 0) {
            return (int) $fallbackYearNumber;
        }

        return $semester ? max(1, (int) ceil($semester / $spy)) : null;
    }

    public static function yearLabel(?string $structureType, ?int $semester, ?int $fallbackYearNumber = null, int $semestersPerYear = 2): string
    {
        $yearNumber = self::yearNumber($structureType, $semester, $fallbackYearNumber, $semestersPerYear);

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
