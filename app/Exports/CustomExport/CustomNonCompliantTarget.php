<?php

namespace App\Exports\CustomExport;

use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class CustomNonCompliantTarget extends ExcelExport implements WithDrawings
{
    public function headings(): array
    {
        return [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['NON-COMPLIANT TARGETS'],
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

                'Institution',
                'Institution Type',
                'Institution Class',

                'District',
                'Municipality',
                'Province',
                'Region',

                'Qualification Code',
                'Qualification Title',
                'Scholarship Program',

                'ABDD Sector',
                'TVET Sector',
                'Priority Sector',

                'Delivery Mode',
                'Learning Mode',

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
                'Remarks',
                'Others',
                'Status',
            ],
        ];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('TESDA Logo');
        $drawing->setDescription('TESDA Logo');
        $drawing->setPath(public_path('images/TESDA_logo.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('Z1');
        $drawing->setOffsetX(0);
        $drawing->setOffsetY(0);

        return $drawing;
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

                $subHeaderStyle = [
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
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '7a8078'],
                        ],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'D3D3D3'],
                    ],
                ];

                $sheet->getStyle("A1:A3")->applyFromArray($headerStyle);
                $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray($subHeaderStyle);
                $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray($boldStyle);

                foreach (range(1, $columnCount) as $colIndex) {
                    $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
                }

                $dynamicBorderStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ];

                $row = 6;
                while (true) {
                    $hasData = false;
                    foreach (range(1, $columnCount) as $colIndex) {
                        $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                        if ($sheet->getCell("{$columnLetter}{$row}")->getValue() !== null) {
                            $hasData = true;
                            break;
                        }
                    }
                    if (!$hasData) {
                        break;
                    }
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray($dynamicBorderStyle);
                    $row++;
                }
            }
        ];
    }
}
