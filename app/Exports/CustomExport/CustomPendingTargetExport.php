<?php

namespace App\Exports\CustomExport;

use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use pxlrbt\FilamentExcel\Exports\ExcelExport;


class CustomPendingTargetExport extends ExcelExport implements WithDrawings, WithColumnWidths
{
    public function headings(): array
    {
        return [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['PENDING TARGETS'],
            [''],
            [
                'Fund Source',
                'Source of Fund',
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

                'No. of Slots',
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
    public function drawings()
    {
        $tesda_logo = new Drawing();
        $tesda_logo->setName('TESDA Logo');
        $tesda_logo->setDescription('TESDA Logo');
        $tesda_logo->setPath(public_path('images/TESDA_logo.png'));
        $tesda_logo->setHeight(70);
        $tesda_logo->setCoordinates('U1');
        $tesda_logo->setOffsetX(120);
        $tesda_logo->setOffsetY(0);

        $tuv_logo = new Drawing();
        $tuv_logo->setName('TUV Logo');
        $tuv_logo->setDescription('TUV Logo');
        $tuv_logo->setPath(public_path('images/TUV_Sud_logo.svg.png'));
        $tuv_logo->setHeight(55);
        $tuv_logo->setCoordinates('X1');
        $tuv_logo->setOffsetX(100);
        $tuv_logo->setOffsetY(8);

        return [$tesda_logo, $tuv_logo];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 30,
            'D' => 50,
            'E' => 20,
            'F' => 20,
            'G' => 15,
            'H' => 50,
            'I' => 20,
            'J' => 20,
            'K' => 20,
            'L' => 20,
            'M' => 20,
            'N' => 20,
            'O' => 20,
            'P' => 40,
            'Q' => 20,
            'R' => 40,
            'S' => 40,
            'T' => 40,
            'U' => 40,
            'V' => 40,
            'W' => 20,
            'X' => 20,
            'Y' => 20,
            'Z' => 25,
            'AA' => 20,
            'AB' => 25,
            'AC' => 25,
            'AD' => 20,
            'AE' => 20,
            'AF' => 20,
            'AG' => 20,
            'AH' => 20,
            'AI' => 30,
            'AJ' => 30,
            'AK' => 30,
            'AL' => 30,
            'AM' => 30,
            'AN' => 30,
            'AO' => 30,
            'AP' => 30,
            'AQ' => 30,
            'AR' => 30,
            'AS' => 30,
            'AT' => 20,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $columnCount = count($this->columns);
                $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

                $startColumnIndex = Coordinate::columnIndexFromString('X');
                $endColumnIndex = Coordinate::columnIndexFromString('AS');

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

                $sheet->getRowDimension(5)->setRowHeight(25); // Adjust padding by changing row height
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
