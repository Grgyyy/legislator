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
        'particular.name' => 'Legislator',
        'scholarship_program.name' => 'Scholarship Program',
        'allocation' => 'Allocation',
        'admin_cost' => 'Admin Cost',
        'admin_cost_difference' => 'Allocation - Admin Cost',
        'attribution_sent' => 'Attribution Sent',
        'attribution_received' => 'Attribution Received',
        'expended_funds' => 'Funds Expended',
        'balance' => 'Balance',
        'year' => 'Year',
    ];

    public function query()
    {
        return Allocation::query()
            ->with([
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
                'attribution_sent',
                'attribution_received',
                'year'
            ]);
    }

    public function map($record): array
    {
        return [
            $record->soft_or_commitment,
            $this->getParticularName($record),
            $record->scholarship_program->name ?? 'Unknown Scholarship Program',
            $record->allocation,
            $record->admin_cost,
            $record->allocation - $record->admin_cost,
            $record->attribution_sent,
            $record->attribution_received,
            $this->getExpenses($record),
            $record->balance,
            $record->year,
        ];
    }

    public function getExpenses($record)
    {
        $fundsExpended = $record->target->sum('total_amount');

        return number_format($fundsExpended, 2);
    }

    protected function getParticularName($record): string
    {
        $particular = $record->particular;

        if (!$particular) {
            return 'Unknown Particular Name';
        }

        $subParticularName = $particular->subParticular->name ?? 'Unknown Sub-Particular Name';
        $fundSourceName = $particular->subParticular->fundSource->name ?? 'Unknown Fund Source Name';
        $regionName = $particular->district->province->region->name ?? 'Unknown Region Name';
        $underMunicipalityName = $particular->district->underMunicipality->name ?? 'Unknown Municipality Name';
        $provinceName = $particular->province->name ?? 'Unknown Province Name';
        $partyListName = $particular->partylist->name ?? 'Unknown Party-list Name';
        $districtName = $particular->district->name ?? 'Unknown District Name';

        if ($fundSourceName === "CO Regular" || $fundSourceName === "RO Regular") {
            return "$subParticularName - $regionName";
        } elseif ($fundSourceName === "CO Legislator Funds" && $subParticularName === "District") {
            return $regionName === "NCR"
                ? "$districtName - $underMunicipalityName"
                : $provinceName;
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
