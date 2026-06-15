<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class IncomeReportSheet implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(
        private string $sheetTitle,
        private array  $headers,
        private array  $rows,
        private string $instituteName,
        private string $filterLabel,
    ) {}

    public function title(): string
    {
        return substr($this->sheetTitle, 0, 31);
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->insertNewRowBefore(1, 2);
        $sheet->setCellValue('A1', $this->instituteName . ' — ' . $this->sheetTitle);
        $sheet->setCellValue('A2', $this->filterLabel);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);

        $headerRow = 3;
        $lastCol   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->headers));
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1a1a2e']],
        ]);
    }
}
