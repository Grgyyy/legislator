<?php

namespace App\Exports;

use App\Models\Allocation;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AllocationExport implements FromQuery, WithMapping, WithStyles, WithHeadings
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
                'attributor', // Load the attributor relationship
                'attributorParticular.subParticular', // Fix nested subParticular
                'legislator', // Load legislator
                'particular.subParticular.fundSource', // Nested relationships
                'particular.district.province.region', // Nested relationships
                'particular.district.underMunicipality', // Nested municipality
                'particular.partylist', // Partylist relationship
                'scholarship_program' // Scholarship program
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
            return '-';
        }

        $subParticularName = $particular->subParticular->name ?? '-';
        $fundSourceName = $particular->subParticular->fundSource->name ?? '-';
        $regionName = $particular->district->province->region->name ?? '-';
        $underMunicipalityName = $particular->district->underMunicipality->name ?? '-';
        $provinceName = $particular->province->name ?? '-';
        $partyListName = $particular->partylist->name ?? '-';
        $districtName = $particular->district->name ?? '-';

        // if ($fundSourceName === "CO Regular" || $fundSourceName === "RO Regular") {
        //     return "$subParticularName - $regionName";
        // } elseif ($subParticularName === 'District') {
        //     if ($regionName === 'NCR') {
        //         return "$districtName -  $underMunicipalityName";
        //     } else {
        //         return "$districtName - $provinceName";
        //     }
        // }
        // // else {
        // //     return $subParticularName;
        // // }

        // // elseif ($fundSourceName === "CO Legislator Funds" && $subParticularName === "District") {
        // //     return $regionName === "NCR"
        // //         ? "$districtName - $underMunicipalityName"
        // //         : $provinceName;
        // // }
        // elseif ($subParticularName === "Party-list") {
        //     return $partyListName;
        // } elseif (in_array($subParticularName, ["Senator", "House Speaker", "House Speaker (LAKAS)"], true)) {
        //     return $subParticularName;
        // }

        // return "N/A";


        if ($fundSourceName === "CO Regular" || $fundSourceName === "RO Regular") {
            return "$subParticularName - $regionName";
        } elseif ($subParticularName === 'District') {
            if ($regionName === 'NCR') {
                return "$districtName - $underMunicipalityName";
            } else {
                return "$districtName - $provinceName";
            }
        } elseif ($subParticularName === "Party-list") {
            return $partyListName;
        } elseif (in_array($subParticularName, ["Senator", "House Speaker", "House Speaker (LAKAS)"], true)) {
            return $subParticularName;
        }
        return "N/A";
    }
    public function headings(): array
    {
        $customHeadings = [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['ALLOCATION EXPORT'],
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
}
