<?php

namespace App\Exports;

use App\Models\Target;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PendingTargetExport implements FromQuery, WithHeadings, WithStyles, WithMapping
{
    private $columns = [
        'abscap_id' => 'Absorptive Capacity',
        'fund_source' => 'Fund Source',
        'allocation.legislator.name' => 'Legislator',
        'allocation.soft_or_commitment' => 'Source of Fund',
        'appropriation_type' => 'Appropriation Type',
        'allocation.year' => 'Allocation',
        'allocation.legislator.particular.subParticular' => 'Particular',
        'municipality_name' => 'Municipality',
        'district_name' => 'District',
        'municipality.province.name' => 'Province',
        'region_name' => 'Region',
        'institution_name' => 'Institution',
        'institution_type' => 'Institution Type',
        'institution_class' => 'Institution Class',
        'qualification_code' => 'Qualification Code',
        'qualification_name' => 'Qualification Title',
        'abdd_sector' => 'ABDD Sector',
        'tvet_sector' => 'TVET Sector',
        'priority_sector' => 'Priority Sector',
        'delivery_mode' => 'Delivery Mode',
        'learning_mode' => 'Learning Mode',
        'scholarship_program' => 'Scholarship Program',
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

    public function query()
    {
        return Target::query()
            ->select([
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
                'delivery_mode_id',
                'learning_mode_id',
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
            ])
            ->addSelect([
                'total_amount_per_slot' => DB::raw('
                CASE
                    WHEN number_of_slots = 0 THEN NULL
                    ELSE total_amount / number_of_slots
                END
            ')
            ])
            ->when(request()->user()->role == 'RO', function (Builder $query) {
                $query->where('region_id', request()->user()->region_id);
            })
            ->where('target_status_id', '=', 1);
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }


    public function map($record): array
    {
        return [
            $record->abscap_id,
            $this->getFundSource($record),
            $record->allocation->legislator->name ?? 'No legislator available',
            $record->allocation->soft_or_commitment ?? 'No source of fund available',
            $record->appropriation_type,
            $record->allocation->year,
            $this->getParticular($record),
            $record->municipality->name ?? 'No municipality available',
            $record->district->name ?? 'No district available',
            $record->municipality->province->name ?? 'No province available',
            $record->municipality->province->region->name ?? 'No region available',
            $record->tvi->name ?? 'No institution available',
            $record->tvi->tviClass->tviType->name ?? 'No institution type available',
            $record->tvi->tviClass->name ?? 'No institution class available',
            $record->qualification_title_code ?? 'No qualification code available',
            $record->qualification_title_code ?? 'No qualification title available',
            $record->abdd->name ?? 'No ABDD sector available',
            $record->qualification_title->trainingProgram->tvet->name ?? 'No TVET sector available',
            $record->qualification_title->trainingProgram->priority->name ?? 'No priority sector available',
            $record->deliveryMode->name ?? 'No delivery mode available',
            $record->learningMode->name ?? 'No learning mode available',
            $record->allocation->scholarship_program->name ?? 'No scholarship program available',
            $record->number_of_slots,

            // Cost per slot calculations
            $this->formatCurrency($this->calculateCostPerSlot($record, 'total_training_cost_pcc')),
            $this->formatCurrency($this->calculateCostPerSlot($record, 'total_cost_of_toolkit_pcc')),
            $this->formatCurrency($this->calculateCostPerSlot($record, 'total_training_support_fund')),
            $this->formatCurrency($this->calculateCostPerSlot($record, 'total_assessment_fee')),
            $this->formatCurrency($this->calculateCostPerSlot($record, 'total_entrepreneurship_fee')),
            $this->formatCurrency($this->calculateCostPerSlot($record, 'total_new_normal_assisstance')),
            $this->formatCurrency($this->calculateCostPerSlot($record, 'total_accident_insurance')),
            $this->formatCurrency($this->calculateCostPerSlot($record, 'total_book_allowance')),
            $this->formatCurrency($this->calculateCostPerSlot($record, 'total_uniform_allowance')),
            $this->formatCurrency($this->calculateCostPerSlot($record, 'total_misc_fee')),
            $this->formatCurrency($this->calculateCostPerSlot($record, 'total_amount')),


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
        ];
    }



    private function getFundSource($record)
    {
        $fundSource = $record->allocation
            ->particular
            ->subParticular
            ->fundSource;

        if ($fundSource) {
            return $fundSource->name ?? 'No fund source name available';
        }

        return 'No fund source available';
    }

    private function getParticular($record)
    {
        $particulars = $record->allocation->legislator->particular;

        if ($particulars->isNotEmpty()) {
            $subParticular = $particulars->first()->subParticular;

            return $subParticular->name ?? 'No sub-particular available';
        }

        return 'No particular available';
    }

    private function formatCurrency($amount)
    {
        return '₱ ' . number_format($amount, 2, '.', ',');
    }

    private function calculateCostPerSlot($record, $costProperty)
    {
        $totalCost = $record->{$costProperty};
        $slots = $record->number_of_slots;

        if ($slots > 0) {
            return $totalCost / $slots;
        }

        return 0;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:' . $this->getLastColumn() . '1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);
    }

    private function getLastColumn()
    {
        $lastColumnIndex = count($this->columns) - 1;
        $lastColumn = '';

        while ($lastColumnIndex >= 0) {
            $lastColumn = chr($lastColumnIndex % 26 + 65) . $lastColumn;
            $lastColumnIndex = floor($lastColumnIndex / 26) - 1;
        }

        return $lastColumn;
    }



}
