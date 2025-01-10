<?php

namespace App\Exports;

use App\Models\Target;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CompliantTargetExport implements FromQuery, WithHeadings, WithStyles, WithMapping
{
    private array $columns = [
        'fund_source' => 'Allocation Type',
        'attributionAllocation.legislator.name' => 'Legislator I',
        'allocation.legislator.name' => 'Legislator II',
        'allocation.legislator.particular.subParticular' => 'Particular',
        'allocation.soft_or_commitment' => 'Source of Fund',
        'appropriation_type' => 'Appropriation Type',
        'allocation.year' => 'Allocation Year',
        'district_name' => 'District',
        'municipality_name' => 'Municipality',
        'municipality.province.name' => 'Province',
        'region_name' => 'Region',
        'institution_name' => 'Institution',
        'scholarship_program' => 'Scholarship Program',
        'qualification_name' => 'Qualification Title',
        'priority_sector' => 'Priority Sector',
        'tvet_sector' => 'TVET Sector',
        'abdd_sector' => 'ABDD Sector',
        'number_of_slots' => 'No. of slots',
        'training_cost_per_slot' => 'Training Cost',
        'cost_of_toolkit_per_slot' => 'Cost of Toolkit',
        'training_support_fund_per_slot' => 'Training Support Fund',
        'assessment_fee_per_slot' => 'Assessment Fee',
        'entrepreneurship_fee_per_slot' => 'Entrepreneurship Fee',
        'new_normal_assistance_per_slot' => 'New Normal Assistance',
        'accident_insurance_per_slot' => 'Accident Insurance',
        'book_allowance_per_slot' => 'Book Allowance',
        'uniform_allowance_per_slot' => 'Uniform Allowance',
        'misc_fee_per_slot' => 'Miscellaneous Fee',
        'total_amount_per_slot' => 'PCC',
        'total_training_cost_pcc' => 'Total Training Cost',
        'total_cost_of_toolkit_pcc' => 'Total Cost of Toolkit',
        'total_training_support_fund' => 'Total Training Support Fund',
        'total_assessment_fee' => 'Total Assessment Fee',
        'total_entrepreneurship_fee' => 'Total Entrepreneurship Fee',
        'total_new_normal_assisstance' => 'Total New Normal Assistance',
        'total_accident_insurance' => 'Total Accident Insurance',
        'total_book_allowance' => 'Total Book Allowance',
        'total_uniform_allowance' => 'Total Uniform Allowance',
        'total_misc_fee' => 'Total Miscellaneous Fee',
        'total_amount' => 'Total PCC',
        'status' => 'Status',
    ];

    public function query(): Builder
    {
        return Target::query()
            ->select($this->getSelectColumns())
            ->addSelect(['total_amount_per_slot' => $this->calculatePerSlot('total_amount')])
            ->when(request()->user()->role === 'RO', function (Builder $query) {
                $query->where('region_id', request()->user()->region_id);
            })
            ->where('target_status_id', 2)
            ->whereNull('attribution_allocation_id');
    }

    public function headings(): array
    {
        return [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['COMPLIANT TARGET'],
            [''],
            array_values($this->columns),
        ];
    }

    public function map($record): array
    {
        return array_merge([
            $this->getFundSource($record),
            $record->attributionAllocation->legislator->name ?? '-',
            $record->allocation->legislator->name ?? '-',
            $this->getParticular($record),
            $record->allocation->soft_or_commitment ?? '-',
            $record->appropriation_type,
            $record->allocation->year,
            $record->tvi->district->name ?? '-',
            $record->municipality->name ?? '-',
            $record->municipality->province->name ?? '-',
            $record->municipality->province->region->name ?? '-',
            $record->tvi->name ?? '-',
            $record->allocation->scholarship_program->name ?? '-',
            $record->qualification_title->trainingProgram->title ?? '-',
            $record->qualification_title->trainingProgram->priority->name ?? '-',
            $record->qualification_title->trainingProgram->tvet->name ?? '-',
            $record->abdd->name ?? '-',
            $record->number_of_slots,
        ], $this->mapCostPerSlot($record), [
            $this->formatCurrency($record->total_training_cost_pcc),
            $this->formatCurrency($record->total_cost_of_toolkit_pcc),
            $this->formatCurrency($record->total_training_support_fund),
            $this->formatCurrency($record->total_assessment_fee),
            $this->formatCurrency($record->total_entrepreneurship_fee),
            $this->formatCurrency($record->total_new_normal_assisstance),
            $this->formatCurrency($record->total_accident_insurance),
            $this->formatCurrency($record->total_book_allowance),
            $this->formatCurrency($record->total_uniform_allowance),
            $this->formatCurrency($record->total_misc_fee),
            $this->formatCurrency($record->total_amount),
            $record->targetStatus->desc ?? 'No status available',
        ]);
    }

    // Helper method to fetch fund source
    private function getFundSource($record): string
    {
        return $record->allocation->particular->subParticular->fundSource->name ?? 'No fund source available';
    }

    // Helper method to fetch particular details
    private function getParticular($record): string
    {
        $particulars = $record->allocation->legislator->particular;
        return $particulars->isNotEmpty() ? $particulars->first()->subParticular->name ?? 'No sub-particular available' : 'No particular available';
    }

    // Helper method to format currency values
    private function formatCurrency($amount): string
    {
        $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount, 'PHP');
    }

    // Helper method to calculate cost per slot
    private function calculateCostPerSlot($record, string $costProperty): float
    {
        $totalCost = $record->{$costProperty};
        $slots = $record->number_of_slots;
        return $slots > 0 ? $totalCost / $slots : 0;
    }

    // Helper method to map costs per slot
    private function mapCostPerSlot($record): array
    {
        $costProperties = [
            'total_training_cost_pcc',
            'total_cost_of_toolkit_pcc',
            'total_training_support_fund',
            'total_assessment_fee',
            'total_entrepreneurship_fee',
            'total_new_normal_assisstance',
            'total_accident_insurance',
            'total_book_allowance',
            'total_uniform_allowance',
            'total_misc_fee',
            'total_amount',
        ];

        return array_map(fn($property) => $this->formatCurrency($this->calculateCostPerSlot($record, $property)), $costProperties);
    }

    // Helper method to define select columns for the query
    private function getSelectColumns(): array
    {
        return [
            'abscap_id',
            'allocation_id',
            'district_id',
            'municipality_id',
            'tvi_id',
            'tvi_name',
            'abdd_id',
            'qualification_title_id',
            'qualification_title_code',
            'qualification_title_name',
            'number_of_slots',
            'total_training_cost_pcc',
            'total_cost_of_toolkit_pcc',
            'total_training_support_fund',
            'total_assessment_fee',
            'total_entrepreneurship_fee',
            'total_new_normal_assisstance',
            'total_accident_insurance',
            'total_book_allowance',
            'total_uniform_allowance',
            'total_misc_fee',
            'total_amount',
            'appropriation_type',
            'target_status_id',
        ];
    }

    // Helper method to calculate slot-based values
    private function calculatePerSlot(string $field)
    {
        return DB::raw("CASE WHEN number_of_slots = 0 THEN NULL ELSE {$field} / number_of_slots END");
    }

    public function styles(Worksheet $sheet)
    {
        $columnCount = count($this->columns);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        // Merge cells for the header
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->mergeCells("A4:{$lastColumn}4");

        // Define reusable style configurations
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

        // Apply styles
        $sheet->getStyle("A1:A3")->applyFromArray($headerStyle);
        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray($subHeaderStyle);
        $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray($boldStyle);

        // Dynamically adjust the width of each column
        foreach (range(1, $columnCount) as $colIndex) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }
    }
}
