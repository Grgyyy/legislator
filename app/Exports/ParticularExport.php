<?php

namespace App\Exports;

use App\Models\Particular;
use App\Models\SubParticular;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ParticularExport implements FromQuery, WithMapping, WithStyles, WithHeadings
{
    private array $columns = [
        'subParticular.name' => 'Particular Type',
        'subParticular.fundSource.name' => 'Fund Source',
        'partylist.name' => 'Party-list',
        'district.name' => 'District',
        'district.underMunicipality.name' => 'Municipality',
        'district.province.name' => 'Province',
        'district.province.region.name' => 'Region',
    ];

    public function query(): Builder
    {
        return Particular::query()
            ->select('particulars.*')
            ->join('districts', 'particulars.district_id', '=', 'districts.id')
            ->join('provinces', 'districts.province_id', '=', 'provinces.id')
            ->join('regions', 'provinces.region_id', '=', 'regions.id')
            ->orderBy('regions.name', 'asc');
    }

    public function map($record): array
    {
        return [
            $record->subParticular->name ?? '-',
            $record->subParticular->fundSource->name ?? '-',
            $record->partylist->name ?? '-',
            $record->district->name ?? '-',
            $record->district->underMunicipality->name ?? '-',
            $record->district->province->name ?? '-',
            $record->district->province->region->name ?? '-',

        ];
    }

    public function headings(): array
    {
        $customHeadings = [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['PARTICULARS'],
            [''],
        ];

        return array_merge($customHeadings, [array_values($this->columns)]);
    }

    public function styles(Worksheet $sheet)
    {
        $columnCount = count($this->columns);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        // Merge cells for headers
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

        // Auto-size columns
        foreach (range(1, $columnCount) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }

        return $sheet;
    }
}
