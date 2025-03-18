<?php

namespace App\Exports;

use App\Models\Allocation;
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

class AllocationExport implements FromQuery, WithMapping, WithStyles, WithHeadings, WithDrawings, WithColumnWidths
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
            $record->allocation ?? '-',
            $record->admin_cost ?? '-',
            $record->allocation - $record->admin_cost ?? '-',
            $this->getExpenses($record),
            $record->balance ?? '-',
            $record->year ?? '-',
        ];
    }

    public function getExpenses($record)
    {
        $fundsExpended = optional($record->target)->sum('total_amount');

        return $fundsExpended !== null ? (float) $fundsExpended : '-';
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
        $tesda_logo = new Drawing();
        $tesda_logo->setName('TESDA Logo');
        $tesda_logo->setDescription('TESDA Logo');
        $tesda_logo->setPath(public_path('images/TESDA_logo.png'));
        $tesda_logo->setHeight(70);
        $tesda_logo->setCoordinates('D1');
        $tesda_logo->setOffsetX(230);
        $tesda_logo->setOffsetY(0);

        $tuv_logo = new Drawing();
        $tuv_logo->setName('TUV Logo');
        $tuv_logo->setDescription('TUV Logo');
        $tuv_logo->setPath(public_path('images/TUV_Sud_logo.svg.png'));
        $tuv_logo->setHeight(55);
        $tuv_logo->setCoordinates('G1');
        $tuv_logo->setOffsetX(0);
        $tuv_logo->setOffsetY(8);

        return [$tesda_logo, $tuv_logo];
    }




    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 50,
            'C' => 50,
            'D' => 50,
            'E' => 50,
            'F' => 30,
            'G' => 30,
            'H' => 30,
            'I' => 30,
            'J' => 30,
            'K' => 30,
            'L' => 20,
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $columnCount = count($this->columns);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        $startColumnIndex = Coordinate::columnIndexFromString('G');
        $endColumnIndex = Coordinate::columnIndexFromString('K');

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
