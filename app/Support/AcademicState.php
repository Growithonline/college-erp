<?php

namespace App\Support;

class AcademicState
{
    public static function yearNumber(?string $structureType, ?int $semester, ?int $fallbackYearNumber = null, int $semestersPerYear = 0): ?int
    {
        $structureType = strtolower((string) $structureType);

        // Explicit SPY from the course record takes priority (handles non-standard
        // configurations like a semester course with 4 parts/year). When the caller
        // does not pass a value (0), fall back to the structure-type convention.
        $spy = $semestersPerYear ?: match ($structureType) {
            'yearly'    => 1,
            'trimester' => 3,
            default     => 2,
        };

        if (in_array($structureType, ['semester', 'trimester', 'yearly']) && $semester) {
            return max(1, (int) ceil($semester / $spy));
        }

        if ($fallbackYearNumber !== null && $fallbackYearNumber > 0) {
            return (int) $fallbackYearNumber;
        }

        return $semester ? max(1, (int) ceil($semester / $spy)) : null;
    }

    public static function yearLabel(?string $structureType, ?int $semester, ?int $fallbackYearNumber = null, int $semestersPerYear = 0): string
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
