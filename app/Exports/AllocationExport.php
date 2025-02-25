<?php

namespace App\Exports;

use App\Models\Allocation;
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

class AllocationExport implements FromQuery, WithMapping, WithStyles, WithHeadings, WithDrawings
{
    private array $columns = [
        'soft_or_commitment' => 'Source of Fund',
        'attributor.name' => 'Attributor',
        'attributorParticular.SubParticular.name' => 'Attributor Particular',
        'legislator.name' => 'Legislator',
        'particular.SubParticular.name' => 'Particular',
        'scholarship_program.name' => 'Scholarship Program',
        'allocation' => 'Allocation',
        'admin_cost' => 'Admin Cost',
        'admin_cost_difference' => 'Allocation - Admin Cost',
        'expended_funds' => 'Funds Expended',
        'balance' => 'Balance',
        'year' => 'Year',
    ];

    public function query()
    {
        return Allocation::query()
            ->with([
                'attributor',
                'attributorParticular.subParticular',
                'legislator',
                'particular.subParticular.fundSource',
                'particular.district.province.region',
                'particular.district.underMunicipality',
                'particular.partylist',
                'scholarship_program'
            ])
            ->select([
                'id',
                'soft_or_commitment',
                'particular_id',
                'scholarship_program_id',
                'allocation',
                'admin_cost',
                'balance',
                'attributor_id',
                'attributor_particular_id',
                'legislator_id',
                'year'
            ]);
    }


    public function map($record): array
    {
        return [
            $record->soft_or_commitment,
            $record->attributor->name ?? '-',
            $record->attributorParticular->subParticular->name ?? '-',
            $record->legislator->name ?? '-',
            $this->getParticularName($record),
            $record->scholarship_program->name ?? '-',
            $this->formatCurrency($record->allocation),
            $this->formatCurrency($record->admin_cost),
            $this->formatCurrency($record->allocation - $record->admin_cost),
            $this->formatCurrency($this->getExpenses($record)),
            $this->formatCurrency($record->balance),
            $record->year,
        ];
    }


    protected function formatCurrency($value): string
    {
        $amount = is_numeric($value) ? (float) $value : 0;

        $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($amount, 'PHP');
    }

    public function getExpenses($record)
    {
        $fundsExpended = $record->target->sum('total_amount');

        return (float) $fundsExpended;
    }
    protected function getParticularName($record): string
    {
        $particular = $record->particular;

        if (!$particular) {
            return 'N/A';
        }

        $district = $particular->district;
        $municipality = $district ? $district->underMunicipality : null;
        $districtName = $district ? $district->name : 'Unknown District';
        $municipalityName = $municipality ? $municipality->name : '';
        $provinceName = $district ? $district->province->name : 'Unknown Province';
        $regionName = $district ? $district->province->region->name : 'Unknown Region';

        $subParticular = $particular->subParticular->name ?? 'Unknown SubParticular';

        $formattedName = '';

        if ($subParticular === 'Party-list') {
            $partylistName = $particular->partylist->name ?? 'Unknown Party-list';
            $formattedName = "{$subParticular} - {$partylistName}";
        } elseif (in_array($subParticular, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
            $formattedName = "{$subParticular}";
        } elseif ($subParticular === 'District') {
            if ($municipalityName) {
                $formattedName = "{$subParticular} - {$districtName}, {$municipalityName}, {$provinceName}";
            } else {
                $formattedName = "{$subParticular} - {$districtName}, {$provinceName}, {$regionName}";
            }
        } elseif ($subParticular === 'RO Regular' || $subParticular === 'CO Regular') {
            $formattedName = "{$subParticular} - {$regionName}";
        } else {
            $formattedName = "{$subParticular} - {$regionName}";
        }

        return $formattedName;
    }
    public function headings(): array
    {
        $customHeadings = [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['ALLOCATIONS'],
            [''],
        ];

        return array_merge($customHeadings, [array_values($this->columns)]);
    }


    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('TESDA Logo');
        $drawing->setDescription('TESDA Logo');
        $drawing->setPath(public_path('images/TESDA_logo.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('D1');
        $drawing->setOffsetX(200);
        $drawing->setOffsetY(0);

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
