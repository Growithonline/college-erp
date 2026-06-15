<?php

namespace App\Exports;

use App\Models\Institute;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class FeeLedgerSummarySheet implements
    FromCollection, WithHeadings, WithMapping,
    WithStyles, WithTitle, WithColumnWidths
{
    private Collection $grouped;
    private ?Institute $institute;

    public function __construct(Collection $grouped, ?Institute $institute)
    {
        $this->grouped   = $grouped;
        $this->institute = $institute;
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function collection(): Collection
    {
        return $this->grouped->map(function ($rows, $courseName) {
            $totalDue = $rows->sum(fn($r) => $r->wallet_balance < 0 ? abs($r->wallet_balance) : 0);
            return (object) [
                'course_name'        => $courseName,
                'student_count'      => $rows->count(),
                'total_invoiced'     => $rows->sum('total_invoiced'),
                'total_paid'         => $rows->sum('total_paid'),
                'total_discount'     => $rows->sum('total_discount'),
                'total_fine'         => $rows->sum('total_fine'),
                'total_library_fine' => $rows->sum('library_fine_due'),
                'total_due'          => $totalDue,
                'due_count'          => $rows->filter(fn($r) => $r->wallet_balance < 0 || $r->library_fine_due > 0)->count(),
            ];
        })->values();
    }

    public function headings(): array
    {
        return [
            'Course',
            'Total Students',
            'Total Invoiced (Rs)',
            'Total Paid (Rs)',
            'Discount (Rs)',
            'Fine (Rs)',
            'Library Fine (Rs)',
            'Total Due (Rs)',
            'Students with Due',
        ];
    }

    public function map($row): array
    {
        return [
            $row->course_name,
            $row->student_count,
            round($row->total_invoiced, 2),
            round($row->total_paid, 2),
            round($row->total_discount, 2),
            round($row->total_fine, 2),
            round($row->total_library_fine, 2),
            round($row->total_due, 2),
            $row->due_count,
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 30, 'B' => 16, 'C' => 20, 'D' => 20, 'E' => 16, 'F' => 14, 'G' => 18, 'H' => 18, 'I' => 20];
    }

    public function styles(Worksheet $sheet): array
    {
        $instituteName = $this->institute?->name ?? 'Institute';
        $sheet->insertNewRowBefore(1, 3);
        $sheet->setCellValue('A1', $instituteName);
        $sheet->setCellValue('A2', 'Fee Ledger Report — Course Wise Summary');
        $sheet->setCellValue('A3', 'Generated: ' . now()->format('d M Y, h:i A'));
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            2 => ['font' => ['bold' => true, 'size' => 11], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e40af']],
            ],
        ];
    }
}
