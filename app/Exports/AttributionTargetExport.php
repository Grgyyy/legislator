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

class AttributionTargetExport implements FromQuery, WithHeadings, WithStyles, WithMapping
{
    /**
     * Column definitions for export with their headings.
     */
    private $columns = [
        // 'abscap_id' => 'Absorptive Capacity',
        'fund_source' => 'Fund Source',
        'allocation.soft_or_commitment' => 'Source of Fund',
        'attributionAllocation.legislator.name' => 'Attributor',
        'attributionAllocation.legislator.particular.subParticular' => 'Attribution Particular',
        'allocation.legislator.name' => 'Legislator',
        'allocation.legislator.particular.subParticular' => 'Particular',
        'appropriation_type' => 'Appropriation Type',
        'allocation.year' => 'Allocation',

        'institution_name' => 'Institution',
        'district_name' => 'District',
        'municipality_name' => 'Municipality',
        'municipality.province.name' => 'Province',
        'region_name' => 'Region',
        'institution_class' => 'Institution Class',
        'institution_type' => 'Institution Type',

        'qualification_title_code' => 'Qualification Code',
        'qualification_title_name' => 'Qualification Title',

        'abdd.name' => 'ABDD Sector',
        'qualification_title.trainingProgram.tvet.name' => 'TVET Sector',
        'qualification_title.trainingProgram.priority.name' => 'Priority Sector',

        'deliveryMode.name' => 'Delivery Mode',
        'learningMode.name' => 'Learning Mode',

        'allocation.scholarship_program.name' => 'Scholarship Program',

        'number_of_slots' => 'No. of Slots',
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
                'total_amount_per_slot' => DB::raw('CASE WHEN number_of_slots = 0 THEN NULL ELSE total_amount / number_of_slots END')
            ])
            ->when(request()->user()->role === 'RO', function (Builder $query) {
                $query->where('region_id', request()->user()->region_id);
            })
            ->where('target_status_id', 1)
            ->whereHas('allocation', function ($query) {
                $query->whereNotNull('attributor_id');
            });

    }


    public function headings(): array
    {
        $customHeadings = [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['ATTRIBUTION TARGET'],
            [''],
        ];

        return array_merge($customHeadings, [array_values($this->columns)]);
    }
    public function map($record): array
    {
        return [
            // $record->abscap_id,
            $this->getFundSource($record),
            $record->allocation->soft_or_commitment ?? '-',
            $this->getAttributor($record),
            $this->getAttributionParticular($record),
            $this->getLegislator($record->allocation),
            $this->getParticular($record),
            $record->appropriation_type,
            $record->allocation->year,

            $record->tvi->name ?? '-',
            $record->district->name ?? '-',
            $record->municipality->name ?? '-',
            $record->municipality->province->name ?? '-',
            $record->municipality->province->region->name ?? '-',
            $record->tvi->tviClass->name ?? '-',
            $record->tvi->tviType->name ?? '-',

            $record->qualification_title_code ?? '-',
            $record->qualification_title_name ?? '-',

            $record->abdd->name ?? '-',
            $record->qualification_title->trainingProgram->tvet->name ?? '-',
            $record->qualification_title->trainingProgram->priority->name ?? '-',

            $record->deliveryMode->name ?? '-',
            $record->learningMode->name ?? '-',

            $record->allocation->scholarship_program->name ?? '-',

            $record->number_of_slots,
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
            $record->targetStatus->desc ?? '-',
        ];
    }
    private function getAttributor($record)
    {
        return $record->allocation->attributor->name ?? '-';
    }
    private function getAttributionParticular($record)
    {
        $particular = $record?->allocation?->attributorParticular;
        return $particular?->subParticular?->name ?? '-';
    }

    private function getLegislator($allocation)
    {
        return $allocation?->legislator?->name ?? '-';
    }

    private function getParticular($record)
    {
        $particular = $record->allocation?->particular;
        return $particular?->subParticular?->name ?? '-';
    }
    private function getFundSource($record)
    {
        $particular = $record->allocation?->attributorParticular ?? $record->allocation?->particular;
        return $particular?->subParticular?->fundSource?->name ?? '-';
    }

    private function formatCurrency($amount)
    {
        $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount, 'PHP');
    }

    private function calculateCostPerSlot($record, $costProperty)
    {
        return $record->number_of_slots > 0 ? $record->{$costProperty} / $record->number_of_slots : 0;
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
    }

}
