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

class CustomParticularExport extends ExcelExport implements WithDrawings, WithColumnWidths
{
    public function headings(): array
    {
        return [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['PARTICULARS'],
            [''],
            ['Particular Type', 'Fund Source', 'Party-list', 'District', 'Municipality', 'Province', 'Region'],
        ];
    }

    public function drawings()
    {
        $tesda_logo = new Drawing();
        $tesda_logo->setName('TESDA Logo');
        $tesda_logo->setDescription('TESDA Logo');
        $tesda_logo->setPath(public_path('images/TESDA_logo.png'));
        $tesda_logo->setHeight(70);
        $tesda_logo->setCoordinates('C1');
        $tesda_logo->setOffsetX(0);
        $tesda_logo->setOffsetY(0);

        $tuv_logo = new Drawing();
        $tuv_logo->setName('TUV Logo');
        $tuv_logo->setDescription('TUV Logo');
        $tuv_logo->setPath(public_path('images/TUV_Sud_logo.svg.png'));
        $tuv_logo->setHeight(55);
        $tuv_logo->setCoordinates('F1');
        $tuv_logo->setOffsetX(-50);
        $tuv_logo->setOffsetY(8);

        return [$tesda_logo, $tuv_logo];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 40,
            'C' => 40,
            'D' => 30,
            'E' => 40,
            'F' => 40,
            'G' => 30,
        ];
    }



    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $columnCount = count($this->columnWidths());
                $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

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
