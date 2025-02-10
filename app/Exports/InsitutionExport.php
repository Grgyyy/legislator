<?php

namespace App\Exports;

use App\Models\Tvi;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InsitutionExport implements FromQuery, WithHeadings, WithStyles, WithMapping
{
    private $columns = [
        'school_id' => 'School ID',
        'name' => 'Institution',
        'institution_class_id' => 'Institution Class (A)',
        'tvi_class_id' => 'Institution Class (B)',
        'district_id' => 'District',
        'municipality_id' => 'Municipality',
        'district.province' => 'Province',
        'address' => 'Address',
    ];

    public function query(): Builder
    {
        return Tvi::query()
            ->with(['district.province', 'InstitutionClass', 'tviClass', 'municipality']);
    }

    public function headings(): array
    {
        $customHeadings = [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['INSTITUTIONS'],
            [''],
        ];

        return array_merge($customHeadings, [array_values($this->columns)]);
    }

    public function map($record): array
    {
        return [
            $record->school_id,
            $record->name,
            optional($record->InstitutionClass)->name ?? '-',
            optional($record->tviClass)->name ?? '-',
            optional($record->municipality)->name ?? '-',
            optional($record->district)->name ?? '-',
            optional($record->district->province)->name ?? '-',
            $record->address ?? '-',
        ];

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
