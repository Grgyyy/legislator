<?php

namespace App\Exports;

use App\Models\QualificationTitle;
use App\Models\Toolkit;
use App\Models\TrainingProgram;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use function Filament\Support\format_money;

class ToolkitExport implements FromQuery, WithMapping, WithStyles, WithHeadings
{
    private array $columns = [
        'qualificationTitles.trainingProgram.title' => 'Qualification Titles',
        'lot_name' => 'Lot Name',
        'price_per_toolkit' => 'Price per Toolkit',
        'available_number_of_toolkits' => 'Available Number of Toolkits Per Lot',
        'number_of_toolkits' => 'No. of Toolkits',
        'total_abc_per_lot' => 'Total ABC per Lot',
        'number_of_items_per_toolkit' => 'No. of Items per Toolkit',
        'year' => 'Year',
    ];

    public function query(): Builder
    {
        return Toolkit::query()
            ->with('qualificationTitles.trainingProgram'); // Make sure to eager load the relationship
    }

    public function map($record): array
    {
        return [
            $this->getQualificationTitle($record),
            $record->lot_name ?? '-',
            $this->formatCurrency($record->price_per_toolkit) ?? '-',
            $this->formatCurrency($record->available_number_of_toolkits) ?? '-',
            $this->formatCurrency($record->number_of_toolkits) ?? '-',
            $this->formatCurrency($record->total_abc_per_lot) ?? '-',
            $this->formatCurrency($record->number_of_items_per_toolkit) ?? '-',
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

    public function styles(Worksheet $sheet)
    {
        $columnCount = count($this->columns);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->mergeCells("A4:{$lastColumn}4");

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
        ];

        $sheet->getStyle("A1:A3")->applyFromArray($headerStyle);
        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray($subHeaderStyle);
        $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray($boldStyle);

        foreach (range(1, $columnCount) as $colIndex) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        return $sheet;
    }

    private function formatCurrency($amount)
    {
        // Use the NumberFormatter class to format the currency in the Filipino Peso (PHP)
        $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount, 'PHP');
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
}
