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
        $tesda_logo = new Drawing();
        $tesda_logo->setName('TESDA Logo');
        $tesda_logo->setDescription('TESDA Logo');
        $tesda_logo->setPath(public_path('images/TESDA_logo.png'));
        $tesda_logo->setHeight(70);
        $tesda_logo->setCoordinates('X1');
        $tesda_logo->setOffsetX(20);
        $tesda_logo->setOffsetY(0);

        $tuv_logo = new Drawing();
        $tuv_logo->setName('TUV Logo');
        $tuv_logo->setDescription('TUV Logo');
        $tuv_logo->setPath(public_path('images/TUV_Sud_logo.svg.png'));
        $tuv_logo->setHeight(55);
        $tuv_logo->setCoordinates('AB1');
        $tuv_logo->setOffsetX(0);
        $tuv_logo->setOffsetY(8);

        return [$tesda_logo, $tuv_logo];
    }


    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 30,
            'D' => 40,
            'E' => 30,
            'F' => 40,
            'G' => 20,
            'H' => 20,
            'I' => 15,
            'J' => 50,
            'K' => 20,
            'L' => 20,
            'M' => 20,
            'N' => 20,
            'O' => 20,
            'P' => 20,
            'Q' => 20,
            'R' => 40,
            'S' => 20,
            'T' => 40,
            'U' => 40,
            'V' => 40,
            'W' => 40,
            'X' => 40,
            'Y' => 20,
            'Z' => 20,
            'AA' => 20,
            'AB' => 25,
            'AC' => 20,
            'AD' => 25,
            'AE' => 25,
            'AF' => 20,
            'AG' => 20,
            'AH' => 20,
            'AI' => 20,
            'AJ' => 20,
            'AK' => 30,
            'AL' => 30,
            'AM' => 30,
            'AN' => 30,
            'AO' => 30,
            'AP' => 30,
            'AQ' => 30,
            'AR' => 30,
            'AS' => 30,
            'AT' => 30,
            'AU' => 30,
            'AV' => 50,
            'AW' => 50,
            'AX' => 30,
        ];
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $columnCount = count($this->columns);
                $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

                $startColumnIndex = Coordinate::columnIndexFromString('Z');
                $endColumnIndex = Coordinate::columnIndexFromString('AU');

                for ($colIndex = $startColumnIndex; $colIndex <= $endColumnIndex; $colIndex++) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->getStyle("{$colLetter}6:{$colLetter}1000")
                        ->getNumberFormat()
                        ->setFormatCode('"₱ "#,##0.00');
                }

                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->mergeCells("A3:{$lastColumn}3");
                $sheet->mergeCells("A4:{$lastColumn}4");

                $alignmentStyle = [
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ];

                $headerStyle = array_merge([
                    'font' => ['bold' => true, 'size' => 16],
                ], $alignmentStyle);

                $boldStyle = array_merge([
                    'font' => ['bold' => true],
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
                ], $alignmentStyle);

                $sheet->getRowDimension(5)->setRowHeight(25);
                $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray($boldStyle);


                $sheet->getStyle("A1:A3")->applyFromArray($headerStyle);
                $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray($alignmentStyle);
                $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray($boldStyle);

                foreach (range(1, $columnCount) as $colIndex) {
                    $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->getColumnDimension($columnLetter)
                        ->setAutoSize(false);
                    $sheet->getStyle($columnLetter)->getAlignment()->setWrapText(true);
                    $sheet->getStyle($columnLetter)->applyFromArray($alignmentStyle);
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
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray($alignmentStyle);
                    $row++;
                }
            }
        ];
    }
}
