<?php

namespace App\Traits;

trait ExportsTabularData
{
    protected function exportCsv(array $headers, array $rows, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    protected function exportFeeCollectionExcel(
        array $headers,
        array $rows,
        string $filename,
        string $instituteName,
        string $dateRange,
        string $session
    ): mixed {
        if (!class_exists(\ZipArchive::class)) {
            return $this->exportCsv($headers, $rows, str_replace('.xlsx', '.csv', $filename));
        }
        $tempPath = tempnam(sys_get_temp_dir(), 'fee-coll-');
        $zip = new \ZipArchive();
        $zip->open($tempPath, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRels());
        $zip->addFromString('docProps/app.xml', $this->xlsxAppXml());
        $zip->addFromString('docProps/core.xml', $this->xlsxCoreXml());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRels());
        $zip->addFromString('xl/styles.xml', $this->xlsxFeeCollectionStylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxFeeCollectionSheetXml($headers, $rows, $instituteName, $dateRange, $session));
        $zip->close();
        return response()->download($tempPath, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ])->deleteFileAfterSend(true);
    }

    protected function xlsxFeeCollectionStylesXml(): string
    {
        $fonts = '<fonts count="4">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="13"/><name val="Calibri"/><color rgb="FF1D4ED8"/></font>'
            . '<font><b/><sz val="10"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>'
            . '</fonts>';

        $fills = '<fills count="6">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1E293B"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFDBEAFE"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>'
            . '</fills>';

        $borders = '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFCBD5E1"/></left>'
            . '<right style="thin"><color rgb="FFCBD5E1"/></right>'
            . '<top style="thin"><color rgb="FFCBD5E1"/></top>'
            . '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>';

        $cellStyleXfs = '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';

        $cellXfs = '<cellXfs count="10">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="right"/></xf>'
            . '</cellXfs>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $fonts . $fills . $borders . $cellStyleXfs . $cellXfs
            . '</styleSheet>';
    }

    protected function xlsxFeeCollectionSheetXml(
        array $headers,
        array $rows,
        string $instituteName,
        string $dateRange,
        string $session
    ): string {
        $colCount   = count($headers);
        $lastCol    = $this->xlsxColumnName($colCount);
        $amountCols = [17, 18, 19, 20]; // 0-based: Collection, Fine, Discount, Total
        $colWidths  = [18, 12, 10, 16, 14, 10, 18, 18, 16, 14, 10, 8, 22, 18, 18, 16, 14, 12, 12, 10, 12, 10];

        $totals = array_fill(0, $colCount, null);
        foreach ($amountCols as $ci) {
            $totals[$ci] = 0.0;
            foreach ($rows as $row) {
                $totals[$ci] += (float) str_replace(',', '', $row[$ci] ?? '0');
            }
        }

        $xmlRows = [];

        $xmlRows[] = '<row r="1" ht="24" customHeight="1"><c r="A1" t="inlineStr" s="1"><is><t>'
            . $this->xlsxEscape($instituteName . ' — Fee Collection')
            . '</t></is></c></row>';

        $subtitle = 'Session: ' . $session
            . '   |   Date Range: ' . $dateRange
            . '   |   Generated: ' . now()->setTimezone('Asia/Kolkata')->format('d M Y h:i A');
        $xmlRows[] = '<row r="2" ht="16" customHeight="1"><c r="A2" t="inlineStr" s="2"><is><t>'
            . $this->xlsxEscape($subtitle)
            . '</t></is></c></row>';

        $cells = [];
        foreach ($headers as $ci => $h) {
            $ref     = $this->xlsxColumnName($ci + 1) . '3';
            $cells[] = '<c r="' . $ref . '" t="inlineStr" s="3"><is><t>' . $this->xlsxEscape($h) . '</t></is></c>';
        }
        $xmlRows[] = '<row r="3" ht="20" customHeight="1">' . implode('', $cells) . '</row>';

        foreach ($rows as $rowIdx => $row) {
            $ri     = $rowIdx + 4;
            $isEven = ($rowIdx % 2 === 1);
            $cells  = [];
            foreach (array_values((array) $row) as $ci => $value) {
                $ref     = $this->xlsxColumnName($ci + 1) . $ri;
                $isAmt   = in_array($ci, $amountCols);
                $style   = $isEven ? ($isAmt ? 7 : 6) : ($isAmt ? 5 : 4);
                $cells[] = '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t>'
                    . $this->xlsxEscape((string) $value) . '</t></is></c>';
            }
            $xmlRows[] = '<row r="' . $ri . '">' . implode('', $cells) . '</row>';
        }

        $totRow = count($rows) + 4;
        $cells  = [];
        foreach ($totals as $ci => $val) {
            $ref   = $this->xlsxColumnName($ci + 1) . $totRow;
            $isAmt = in_array($ci, $amountCols);
            $style = $isAmt ? 9 : 8;
            if ($ci === 0) {
                $text = 'TOTAL (' . count($rows) . ' records)';
            } elseif ($val !== null) {
                $text = number_format($val, 2);
            } else {
                $text = '';
            }
            $cells[] = '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t>'
                . $this->xlsxEscape($text) . '</t></is></c>';
        }
        $xmlRows[] = '<row r="' . $totRow . '" ht="18" customHeight="1">' . implode('', $cells) . '</row>';

        $colsXml = '<cols>';
        foreach ($colWidths as $i => $w) {
            $n        = $i + 1;
            $colsXml .= '<col min="' . $n . '" max="' . $n . '" width="' . $w . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        $mergeCells = '<mergeCells count="2">'
            . '<mergeCell ref="A1:' . $lastCol . '1"/>'
            . '<mergeCell ref="A2:' . $lastCol . '2"/>'
            . '</mergeCells>';

        $sheetViews = '<sheetViews><sheetView tabSelected="1" workbookViewId="0">'
            . '<pane ySplit="3" topLeftCell="A4" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . $sheetViews . $colsXml
            . '<sheetData>' . implode('', $xmlRows) . '</sheetData>'
            . $mergeCells
            . '</worksheet>';
    }

    protected function xlsxColumnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name  = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }
        return $name;
    }

    protected function xlsxEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    protected function xlsxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    protected function xlsxRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    protected function xlsxWorkbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Fee Collection" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    protected function xlsxWorkbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    protected function xlsxCoreXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
            . 'xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>Fee Collection Report</dc:title><dc:creator>College ERP</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . now()->toIso8601String() . '</dcterms:created>'
            . '</cp:coreProperties>';
    }

    protected function xlsxAppXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            . 'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>College ERP</Application></Properties>';
    }
}
