<?php

namespace App\Exports;

use App\Models\Legislator;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LegislatorExport implements FromQuery, WithMapping, WithStyles, WithHeadings
{
    private array $columns = [
        'name' => 'Legislator',
        'particular.name' => 'Particular',
        'status_id' => 'Status',
    ];

    public function query(): Builder
    {
        return Legislator::query()
            ->orderBy('name')
            ->with(['particular', 'status']);
    }

    public function map($record): array
    {
        return [
            $record->name ?? '-',
            $this->getParticularNames($record),
            $record->status?->desc ?? '-',
        ];
    }

    public function headings(): array
    {
        $customHeadings = [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['LEGISLATOR'],
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

    protected function getParticularNames($record): string
    {
        return $record->particular->map(function ($particular) {
            $districtName = $particular->district?->name ?? '-';
            $provinceName = $particular->district?->province?->name ?? '-';
            $regionName = $particular->district?->province?->region?->name ?? '-';
            $municipalityName = $particular->district?->underMunicipality?->name ?? null;

            if ($particular->subParticular?->name === 'Party-list') {
                return $particular->subParticular->name . ' - ' . ($particular->partylist?->name ?? '-');
            } elseif (in_array($particular->subParticular?->name, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
                return $particular->subParticular->name;
            } elseif ($particular->subParticular?->name === 'District') {
                return $municipalityName
                    ? "{$districtName}, {$municipalityName}, {$provinceName}"
                    : "{$districtName}, {$provinceName}, {$regionName}";
            } elseif (in_array($particular->subParticular?->name, ['RO Regular', 'CO Regular'])) {
                return $particular->subParticular->name . ' - ' . $regionName;
            } else {
                return $particular->subParticular?->name . ' - ' . $regionName;
            }
        })->implode(', ');
    }
}
