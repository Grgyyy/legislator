<?php

namespace App\Exports;

use App\Models\Allocation;
use App\Models\QualificationTitle;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class TargetReportExport implements FromCollection, WithStyles, WithDrawings, WithColumnWidths
{
    protected $allocationId;

    public function __construct($allocationId)
    {
        $this->allocationId = $allocationId;
    }

    public function collection()
    {
        $allocationYear = $this->getAllocationYear($this->allocationId);
        $scholarshipProgram = $this->getScholarshipProgram($this->allocationId);
        $legisName = $this->getLegislatorName($this->allocationId);
        $particular = $this->getParticularName($this->allocationId);
        $allocation = $this->getAllocation($this->allocationId);
        $adminCost = $this->getAdminCost($this->allocationId);
        $SumTotal = $this->getSumTotal($this->allocationId);

        $targets = $this->targetData($this->allocationId);

        $sumTotalTrainingCost = $this->getSumTotalTrainingCost($this->allocationId);
        $sumTotalCostOfToolkit = $this->getSumTotalCostOfToolkit($this->allocationId);
        $balance = $this->getBalance($this->allocationId);

        $data = [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['TARGET REPORT'],
            ['Year' => 'FY ' . $allocationYear . ' (' . $scholarshipProgram . ')'],
            ['Name of Representative' => 'Name of Representative:', $legisName],
            ['', $particular],
            ['Allocation' => 'Total Allocation:', $allocation],
            ['Admin Cost' => 'Admin Cost:', $adminCost],
            ['Sum of Total Training Cost' => 'Total Training Cost:', $sumTotalTrainingCost],
            ['Sum of Total Cost of Toolkit' => 'Total Cost of Toolkit:', $sumTotalCostOfToolkit],
            ['Sum of Total' => 'Total:', $SumTotal],
            ['Balance' => 'Balance:', $balance],
            [''],
            ['Region', 'Province', 'Municipality', 'Name of Institution', 'Qualification Title', 'Number of Slots', 'Training Cost PCC', 'Cost of Toolkit PCC', 'Total Training Cost', 'Total Cost of Toolkit', 'Total Amount', 'Status'],
            [''],
        ];

        foreach ($targets as $target) {
            $data[] = [
                'Region' => $target->district->province->region->name,
                'Province' => $target->district->province->name,
                'Municipality' => $target->municipality->name,
                'Name of Institution' => $target->tvi->name,
                'Qualification Title' => $target->qualification_title->trainingProgram->title,
                'Number of Slots' => $target->number_of_slots,
                'Training Cost PCC' => $target->number_of_slots > 0
                    ?
                    ($target->total_training_cost_pcc + $target->total_assessment_fee + $target->total_training_support_fund + $target->total_entrepreneurship_fee) / $target->number_of_slots

                    : 0,
                'Cost of Toolkits PCC' => $target->number_of_slots > 0
                    ? $target->total_cost_of_toolkit_pcc / $target->number_of_slots
                    : 0,
                'Total Training Cost' =>
                    ($target->total_training_cost_pcc + $target->total_assessment_fee + $target->total_training_support_fund + $target->total_entrepreneurship_fee) ?? 0
                ,
                'Total Cost of Toolkits' => $target->total_cost_of_toolkit_pcc ?? 0,
                'Total Amount' => $target->total_amount,

                'Status' => $target->targetStatus->desc,
            ];
        }

        return collect($data);
    }

    private function getLegislatorName($id)
    {
        $allocation = Allocation::find($id);

        return $allocation && $allocation->legislator ? $allocation->legislator->name : 'N/A';
    }

    protected function getParticularName($id): string
    {
        $allocation = Allocation::find($id);

        if (!$allocation || !$allocation->particular) {
            return 'Unknown Particular Name';
        }

        $particular = $allocation->particular;
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

    private function targetData($id)
    {
        $allocation = Allocation::find($id);

        return $allocation ? $allocation
            ->target()
            ->get() : collect();
    }

    // private function targetData($id)
    // {
    //     $allocation = Allocation::find($id);

    //     return $allocation ? $allocation
    //         ->target()
    //         ->with('targetStatus')
    //         ->orderByRaw("FIELD(status, 'Pending', 'Compliant', 'Non-Compliant')")
    //         ->get() : collect();
    // }


    private function getAllocationYear($id)
    {
        $allocation = Allocation::find($id);

        return $allocation ? $allocation->year : 'N/A';
    }

    private function getScholarshipProgram($id)
    {
        $qualification_title = QualificationTitle::find($id);

        return $qualification_title && $qualification_title->scholarshipProgram->name
            ? $qualification_title->scholarshipProgram->desc . ' (' . $qualification_title->scholarshipProgram->name . ')'
            : 'N/A';
    }

    private function getAllocation($id)
    {
        $allocation = Allocation::find($id);

        return $allocation ? $allocation->allocation : 0;
    }

    private function getAdminCost($id)
    {
        $allocation = Allocation::find($id);

        if ($allocation && $allocation->legislator) {
            $totalAllocation = $allocation->allocation;
            return $totalAllocation * 0.02;
        }

        return 0;
    }

    private function getSumTotal($allocationId)
    {
        $sum = DB::table('targets')
            ->join('allocations', 'targets.allocation_id', '=', 'allocations.id')
            ->select(
                DB::raw('(
                    (allocations.allocation * 0.02) +
                    SUM(targets.total_training_cost_pcc + targets.total_assessment_fee + targets.total_training_support_fund + targets.total_entrepreneurship_fee) + SUM(targets.total_cost_of_toolkit_pcc)
                ) as sum_of_total_amount')
            )
            ->where('targets.allocation_id', $allocationId)
            ->groupBy('allocations.allocation')
            ->value('sum_of_total_amount');

        return $sum ?? 0;
    }

    private function getBalance($allocationId)
    {
        $sum = DB::table('targets')
            ->join('allocations', 'targets.allocation_id', '=', 'allocations.id')
            ->select(
                DB::raw('(allocations.allocation - (
                (allocations.allocation * 0.02) +
                SUM(targets.total_training_cost_pcc + targets.total_assessment_fee + targets.total_training_support_fund + targets.total_entrepreneurship_fee) +
                SUM(targets.total_cost_of_toolkit_pcc)
            )) as balance')
            )
            ->where('targets.allocation_id', $allocationId)
            ->groupBy('allocations.allocation')
            ->value('sum_of_total_amount');

        return $sum ?? 0;
    }

    private function getSumTotalTrainingCost($allocationId)
    {
        $sum = DB::table('targets')
            ->select(
                DB::raw('SUM(targets.total_training_cost_pcc + targets.total_assessment_fee + targets.total_training_support_fund + targets.total_entrepreneurship_fee)')
            )
            ->where('allocation_id', $allocationId)
            ->value('sum_of_total_training_cost');

        return $sum ?? 0;
    }

    private function getSumTotalCostOfToolkit($allocationId)
    {
        $sum = DB::table('targets')
            ->select(
                DB::raw('SUM(targets.total_cost_of_toolkit_pcc)')
            )
            ->where('allocation_id', $allocationId)
            ->value('sum_of_total_cost_of_toolkits');

        return $sum ?? 0;
    }

    public function drawings()
    {
        $tesda_logo = new Drawing();
        $tesda_logo->setName('TESDA Logo');
        $tesda_logo->setDescription('TESDA Logo');
        $tesda_logo->setPath(public_path('images/TESDA_logo.png'));
        $tesda_logo->setHeight(70);
        $tesda_logo->setCoordinates('E1');
        $tesda_logo->setOffsetX(-50);
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
            'A' => 50,
            'B' => 50,
            'C' => 30,
            'D' => 50,
            'E' => 50,
            'F' => 30,
            'G' => 30,
            'H' => 30,
            'I' => 30,
            'J' => 30,
            'K' => 30,
            'L' => 30,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = [
            'Region',
            'Province',
            'Municipality',
            'Name of Institution',
            'Qualification Title',
            'Number of Slots',
            'Training Cost PCC',
            'Cost of Toolkit PCC',
            'Total Training Cost',
            'Total Cost of Toolkit',
            'Total Amount',
            'Status',
        ];

        $columnCount = count($headerRow);
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

        $sheet->mergeCells("A4:B4");

        $sheet->getStyle("B7:B12")
            ->getNumberFormat()
            ->setFormatCode('"₱ "#,##0.00');


        $alignmentStyle = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $styles = $this->applyHeaderStyle([1, 2, 3]);

        foreach (range(1, $columnCount) as $colIndex) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($columnLetter)
                ->setAutoSize(false);
            $sheet->getStyle($columnLetter)->getAlignment()->setWrapText(true);
            $sheet->getStyle($columnLetter)->applyFromArray($alignmentStyle);
        }

        $underlineStyle = [
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];

        foreach (range(5, 12) as $row) {
            $sheet->getCell("B{$row}")->getStyle()->applyFromArray($underlineStyle);
        }

        $boldStyle = [
            'font' => ['bold' => true],
        ];

        foreach (range(4, 12) as $row) {
            $sheet->getCell("A{$row}")->getStyle()->applyFromArray($boldStyle);
        }

        foreach ([5, 11, 12] as $row) {
            $sheet->getCell("B{$row}")->getStyle()->applyFromArray($boldStyle);
        }

        foreach (range(1, $columnCount) as $colIndex) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->mergeCells("{$columnLetter}14:{$columnLetter}15");
        }

        $mergedRowStyle = [
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'D3D3D3'],
            ],
        ];

        foreach (range(1, $columnCount) as $colIndex) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getCell("{$columnLetter}14")->getStyle()->applyFromArray($mergedRowStyle);
        }

        $rangeBorderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];

        $sheet->getStyle('A4:B12')->applyFromArray($rangeBorderStyle);

        $mergedRangeBorderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '7a8078'],
                ],
            ],
        ];
        $sheet->getStyle('A14:L15')->applyFromArray($mergedRangeBorderStyle);


        $dynamicBorderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];

        $row = 16;
        while ($sheet->getCell("A{$row}")->getValue() !== null || $sheet->getCell("B{$row}")->getValue() !== null || $sheet->getCell("C{$row}")->getValue() !== null || $sheet->getCell("D{$row}")->getValue() !== null || $sheet->getCell("E{$row}")->getValue() !== null || $sheet->getCell("F{$row}")->getValue() !== null || $sheet->getCell("G{$row}")->getValue() !== null || $sheet->getCell("H{$row}")->getValue() !== null || $sheet->getCell("I{$row}")->getValue() !== null || $sheet->getCell("J{$row}")->getValue() !== null || $sheet->getCell("K{$row}")->getValue() || $sheet->getCell("L{$row}")->getValue() !== null) {
            $sheet->getStyle("A{$row}:L{$row}")->applyFromArray($dynamicBorderStyle);
            $row++;
        }

        return $styles;
    }

    private function applyHeaderStyle(array $rows)
    {
        $style = [
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ];

        $styles = [];
        foreach ($rows as $row) {
            $styles[$row] = $style;
        }

        return $styles;
    }
}
