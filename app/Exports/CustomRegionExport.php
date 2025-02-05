<?php

namespace App\Exports;

use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class CustomRegionExport extends ExcelExport
{
    /**
     * Define the custom headings.
     */
    public function headings(): array
    {
        // Custom headers for your sheet
        $customHeadings = [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['REGION'],
            [''],
        ];

        // Add the dynamic column headings
        $columnHeadings = [
            'PSG Code',
            'Region',
        ];

        // Merge custom headings with the dynamic ones
        return array_merge($customHeadings, [$columnHeadings]);
    }

    /**
     * Apply styles to the worksheet.
     */
    public function styles(Worksheet $sheet)
    {
        // Explicitly define the column count based on the headings
        $columnCount = 2; // We have two columns: 'PSG Code' and 'Region'
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        // Merge cells for custom headers (rows 1-4)
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->mergeCells("A4:{$lastColumn}4");

        // Define header style (bold, centered, larger font size)
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        // Define bold style for column headers
        $boldStyle = [
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        // Apply header styles to rows 1-4 (custom headers)
        $sheet->getStyle("A1:{$lastColumn}4")->applyFromArray($headerStyle);

        // Apply bold style to column headers (row 5)
        $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray($boldStyle);

        // Apply styles to the columns below the headers (starting from row 6)
        $sheet->getStyle("A6:{$lastColumn}" . (count($this->data) + 5))
            ->getAlignment()->setWrapText(true); // Ensure text wrapping for the content rows

        // Auto-size columns based on content
        foreach (range(1, $columnCount) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }

        return $sheet;
    }
}
