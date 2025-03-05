<?php

namespace App\Exports;

use App\Models\Toolkit;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ToolkitExport implements FromQuery, WithMapping, WithStyles, WithHeadings, WithDrawings, WithColumnWidths
{
    private array $columns = [
        'qualificationTitles.trainingProgram.title' => 'SOC Title',
        'available_number_of_toolkits' => 'Available Number of Toolkits Per Lot',
        'number_of_toolkits' => 'No. of Toolkits',
        'price_per_toolkit' => 'Price per Toolkit',
        'total_abc_per_lot' => 'Total ABC per Lot',
        'number_of_items_per_toolkit' => 'No. of Items per Toolkit',
        'year' => 'Year',
    ];

    public function query(): Builder
    {
        return Toolkit::query()
            ->with('qualificationTitles.trainingProgram');
    }

    public function map($record): array
    {
        return [
            $this->getQualificationTitle($record),
            $record->available_number_of_toolkits ?? '-',
            $record->number_of_toolkits ?? '-',
            $record->price_per_toolkit ?? '-',
            $record->total_abc_per_lot ?? '-',
            $record->number_of_items_per_toolkit ?? '-',
            $record->year ?? '-',
        ];
    }

    public function headings(): array
    {
        $customHeadings = [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['TOOLKITS'],
            [''],
        ];

        return array_merge($customHeadings, [array_values($this->columns)]);
    }
    private function getQualificationTitle($record)
    {
        $qualificationTitles = $record->qualificationTitles->map(
            fn($qualificationTitle) =>
            optional($qualificationTitle->trainingProgram)->soc_code .
            ' - ' .
            optional($qualificationTitle->trainingProgram)->title
        )->filter()->toArray();

        return empty($qualificationTitles) ? '-' : implode(', ', $qualificationTitles);
    }

    public function drawings()
    {
        $tesda_logo = new Drawing();
        $tesda_logo->setName('TESDA Logo');
        $tesda_logo->setDescription('TESDA Logo');
        $tesda_logo->setPath(public_path('images/TESDA_logo.png'));
        $tesda_logo->setHeight(70);
        $tesda_logo->setCoordinates('B1');
        $tesda_logo->setOffsetX(-50);
        $tesda_logo->setOffsetY(0);

        $tuv_logo = new Drawing();
        $tuv_logo->setName('TUV Logo');
        $tuv_logo->setDescription('TUV Logo');
        $tuv_logo->setPath(public_path('images/TUV_Sud_logo.svg.png'));
        $tuv_logo->setHeight(55);
        $tuv_logo->setCoordinates('D1');
        $tuv_logo->setOffsetX(130);
        $tuv_logo->setOffsetY(8);

        return [$tesda_logo, $tuv_logo];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 100,
            'B' => 40,
            'C' => 30,
            'D' => 30,
            'E' => 30,
            'F' => 30,
            'G' => 30,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $columnCount = count($this->columns);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        $startColumnIndex = Coordinate::columnIndexFromString('D');
        $endColumnIndex = Coordinate::columnIndexFromString('E');

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
}
