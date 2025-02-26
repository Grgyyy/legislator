<?php

namespace App\Exports\CustomExport;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class CustomCompliantTarget extends ExcelExport
{
    public function headings(): array
    {
        return [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['COMPLIANT TARGETS'],
            [''],
            [
                'Fund Source',
                'Source of Fund',
                'Attributor',
                'Attributor Particular',
                'Legislator',
                'Particular',
                'Appropriation Type',
                'Appropriation Year',

                'School ID',
                'Institution',
                'Institution Type',
                'Institution Class',

                'District',
                'Municipality',
                'Province',
                'Region',

                'SOC Code',
                'Qualification Title',
                'Scholarship Program',

                'ABDD Sector',
                'TVET Sector',
                'Priority Sector',

                'Delivery Mode',
                'Learning Mode',

                'Slots',
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
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $columnCount = count($this->getColumns());
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
            }
        ];
    }
}
