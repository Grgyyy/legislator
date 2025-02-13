<?php

namespace App\Exports;

use App\Models\Municipality;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MunicipalityExport implements FromQuery, WithMapping, WithStyles, WithHeadings
{
    private array $columns = [
        'code' => 'PSG Code',
        'name' => 'Municipality',
        'class' => 'Municipality Class',
        'district.name' => 'District',
        'province.name' => 'Province',
        'province.region.name' => 'Region',
    ];

    public function query(): Builder
    {
        return Municipality::query()
            ->select('municipalities.*')
            ->join('district_municipalities', 'municipalities.id', '=', 'district_municipalities.municipality_id')
            ->join('districts', 'district_municipalities.district_id', '=', 'districts.id')
            ->join('provinces', 'municipalities.province_id', '=', 'provinces.id')
            ->join('regions', 'provinces.region_id', '=', 'regions.id')
            ->orderBy('regions.id', 'asc');
    }

    public function map($record): array
    {
        return [
            $record->code ?? '-',
            $record->name ?? '-',
            $record->class ?? '-',
            $this->getDistricts($record) ?? '-',
            $record->province->name ?? '-',
            $record->province->region->name ?? '-',
        ];
    }

    public function headings(): array
    {
        $customHeadings = [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['MUNICIPALITIES'],
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

    private function getDistricts($record)
    {
        $hasMunicipality = $record->district->contains(function ($district) {
            return !is_null($district->underMunicipality->name ?? null);
        });

        if ($hasMunicipality) {
            return $record->district->map(function ($district, $index) use ($record) {
                $municipalityName = $district->underMunicipality->name ?? null;
                $formattedDistrict = $municipalityName
                    ? "{$district->name} - {$municipalityName}"
                    : "{$district->name}";

                return $formattedDistrict;
            })->implode(', ');
        } else {
            $districts = $record->district->pluck('name')->toArray();

            return implode(', ', $districts);
        }
    }

}
