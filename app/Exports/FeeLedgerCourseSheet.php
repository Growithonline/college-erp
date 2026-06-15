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

class FeeLedgerCourseSheet implements
    FromCollection, WithHeadings, WithMapping,
    WithStyles, WithTitle, WithColumnWidths
{
    public function __construct(
        private string $courseName,
        private Collection $rows,
        private ?Institute $institute,
    ) {}

    public function title(): string
    {
        // Sheet name max 31 chars, strip invalid chars
        return mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $this->courseName), 0, 31);
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Sr No', 'Student Name', 'Student ID', 'Roll No', 'Mobile',
            'Father Name', 'Mother Name',
            'Course', 'Stream', 'Year / Sem', 'Session',
            'Total Invoiced (Rs)', 'Total Paid (Rs)', 'Discount (Rs)',
            'Fine (Rs)', 'Library Fine (Rs)', 'Balance Due (Rs)', 'Status',
        ];
    }

    public function map($row): array
    {
        static $sr = 0;
        $sr++;
        $libFine = (float) ($row->library_fine_due ?? 0);
        $due     = $row->wallet_balance < 0 ? abs($row->wallet_balance) : 0;
        $status  = ($due > 0 || $libFine > 0) ? 'Due' : ($row->total_paid > 0 ? 'Paid' : 'No Payment');
        $yearSem = $row->year_number
            ? 'Year ' . $row->year_number
            : ($row->current_semester ? 'Sem ' . $row->current_semester : '-');

        return [
            $sr,
            $row->name,
            $row->student_uid,
            $row->roll_no ?? '',
            $row->mobile ?? '',
            $row->father_name ?? '',
            $row->mother_name ?? '',
            $row->course_name,
            $row->stream_name,
            $yearSem,
            $row->session_name ?? '',
            round($row->total_invoiced, 2),
            round($row->total_paid, 2),
            round($row->total_discount, 2),
            round($row->total_fine, 2),
            round($libFine, 2),
            round($due, 2),
            $status,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 7,  'B' => 26, 'C' => 16, 'D' => 12, 'E' => 14,
            'F' => 22, 'G' => 22, 'H' => 22, 'I' => 18, 'J' => 12,
            'K' => 16, 'L' => 20, 'M' => 18, 'N' => 16, 'O' => 12,
            'P' => 16, 'Q' => 16, 'R' => 14,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $instituteName = $this->institute?->name ?? 'Institute';
        $sheet->insertNewRowBefore(1, 3);
        $sheet->setCellValue('A1', $instituteName);
        $sheet->setCellValue('A2', 'Fee Ledger — ' . $this->courseName);
        $sheet->setCellValue('A3', 'Generated: ' . now()->format('d M Y, h:i A'));
        $sheet->mergeCells('A1:R1');
        $sheet->mergeCells('A2:R2');
        $sheet->mergeCells('A3:R3');

        // Totals row — columns match headings:
        // A=Sr,B=Name,C=ID,D=Roll,E=Mobile,F=Father,G=Mother,
        // H=Course,I=Stream,J=Yr/Sem,K=Session,
        // L=Invoiced,M=Paid,N=Discount,O=Fine,P=LibFine,Q=Due,R=Status
        $count   = $this->rows->count();
        $lastRow = $count + 5; // 3 header rows + 1 heading row + data rows
        $sheet->setCellValue("A{$lastRow}", 'Total');
        $totalDue = $this->rows->sum(fn($r) => $r->wallet_balance < 0 ? abs($r->wallet_balance) : 0);
        $sheet->setCellValue("L{$lastRow}", round($this->rows->sum('total_invoiced'), 2));
        $sheet->setCellValue("M{$lastRow}", round($this->rows->sum('total_paid'), 2));
        $sheet->setCellValue("N{$lastRow}", round($this->rows->sum('total_discount'), 2));
        $sheet->setCellValue("O{$lastRow}", round($this->rows->sum('total_fine'), 2));
        $sheet->setCellValue("P{$lastRow}", round($this->rows->sum('library_fine_due'), 2));
        $sheet->setCellValue("Q{$lastRow}", round($totalDue, 2));

        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            2 => ['font' => ['bold' => true, 'size' => 11], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e40af']],
            ],
            $lastRow => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f1f5f9']]],
        ];
    }
}
