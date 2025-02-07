<?php

namespace App\Exports\CustomExport;

use pxlrbt\FilamentExcel\Exports\ExcelExport;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;


class CustomPendingTargetExport extends ExcelExport
{
    public function headings(): array
    {
        $customHeadings = [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['PENDING TARGETS'],
            [''],
        ];

        $columnHeadings = [
            'Fund Source',
            'Legislator',
            'Source of Fund',
            'Appropriation Type',
            'Allocation',
            'Particular',
            'Municipality',
            'District',
            'Province',
            'Region',
            'Institution',
            'Institution Type',
            'Institution Class',
            'Qualification Code',
            'Qualification SOC Code',
            'Qualification Title',
            'ABDD Sector',
            'TVET Sector',
            'Priority Sector',
            'Delivery Mode',
            'Learning Mode',
            'Scholarship Program',
            'Number of slots',
            'Training Cost',
            'Cost of Toolkit',
            'Training Support Fund',
            'Assessment Fee',
            'Entrepreneurship Fee',
            'New Normal Assistance',
            'Accident Insurance',
            'Book Allowance',
            'Uniform Allowance',
            'Miscellaneous Fee',
            'PCC',
            'Total Training Cost',
            'Total Cost of Toolkit',
            'Total Training Support Fund',
            'Total Assessment Fee',
            'Total Entrepreneurship Fee',
            'Total New Normal Assistance',
            'Total Accident Insurance',
            'Total Book Allowance',
            'Total Uniform Allowance',
            'Total Miscellaneous Fee',
            'Total PCC',
            'Status',
        ];
        return array_merge($customHeadings, [$columnHeadings]);
    }

    public function styles(Worksheet $sheet)
    {
        $columnCount = count($this->columns);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        foreach (range(1, 4) as $row) {
            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        }

        $headerStyle = [
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        $boldStyle = [
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        $sheet->getStyle("A1:A3")->applyFromArray($headerStyle);
        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray($boldStyle);
        $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray($boldStyle);

        foreach (range(1, $columnCount) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }

        return $sheet;
    }
}
