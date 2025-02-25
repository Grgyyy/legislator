<?php

namespace App\Exports;

use App\Models\Municipality;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
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

class MunicipalityExport implements FromQuery, WithMapping, WithStyles, WithHeadings, WithDrawings
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
    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('TESDA Logo');
        $drawing->setDescription('TESDA Logo');
        $drawing->setPath(public_path('images/TESDA_logo.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('D1');
        $drawing->setOffsetX(80);
        $drawing->setOffsetY(1);

        return $drawing;
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
}
