<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class IncomeReportExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private string $instituteName,
        private string $filterLabel,
        private float  $grandTotal,
        private array  $sections,
    ) {}

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->sections as $section) {
            $sheets[] = new IncomeReportSheet(
                $section['title'],
                $section['headers'],
                $section['rows'],
                $this->instituteName,
                $this->filterLabel
            );
        }
        return $sheets;
    }
}
