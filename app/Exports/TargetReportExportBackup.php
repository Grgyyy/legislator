<?php

namespace App\Exports;

use App\Models\Target;
use App\Models\Allocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TargetReportExportBackup implements FromQuery, WithHeadings, WithStyles, WithMapping
{
    private $columns = [
        'region_name' => 'Region',
        'municipality.province.name' => 'Province',
        'institution_name' => 'Name of Institution',
        'qualification_name' => 'Qualification Title',
        'number_of_slots' => 'No. of Slots',

        // PCC
        'training_cost_pcc' => 'Training Cost',
        'cost_of_toolkits_pcc' => 'Cost of Toolkit',

        // TOTAL
        'total_training_cost' => 'Training Cost',
        'total_cost_of_toolkits' => 'Cost of Toolkit',
        'total_amount' => 'TOTAL AMOUNT',
    ];

    public function query()
    {
        return Target::query()
            ->select([
                'targets.*',
                'allocations.admin_cost AS admin_cost',
                'allocations.balance AS balance',
                'legislators.name AS legislator_name',
                'scholarship_programs.name AS scholarship_program_name',
                'provinces.name AS province_name',
            ])
            ->join('allocations', 'targets.allocation_id', 'allocations.id')
            ->leftJoin('legislators', 'allocations.legislator_id', 'legislators.id')
            ->leftJoin('scholarship_programs', 'allocations.scholarship_program_id', 'scholarship_programs.id')
            ->leftJoin('municipalities', 'targets.municipality_id', 'municipalities.id')
            ->leftJoin('provinces', 'municipalities.province_id', 'provinces.id');
    }
    public function headings(): array
    {
        // Retrieve cost data once
        $cost = $this->getCost();

        // Concatenate values into a single string for one cell
        $yearAndSpCell = implode(' ', array_filter([
            'FY',
            $this->getAllocationYear(Target::first()),
            $this->getScholarshipProgramDescription(),
            '(' . $this->getScholarshipProgramCode() . ')'
        ]));

        $particularAndProvince = implode(' - ', array_filter([
            '',
            $this->getParticularName(), // Now based on Target relationships
            $this->getProvince(Target::first()), // Province remains based on the Target
        ]));


        $customHeadings = [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['TARGET REPORT'],
            [''], // Empty row
            [$yearAndSpCell], // Concatenated values in one cell
            ['Name of Representative:', $this->getLegislator(Target::first())],
            ['', $particularAndProvince],
            ['Total Allocation:', $this->formatCurrency($cost->total_allocation ?? 0)],
            ['2% Administrative Cost:', $this->formatCurrency($cost->total_admin_cost ?? 0)],
            ['Total Training Cost:', $this->formatCurrency($cost->sum_of_total_training_cost) ?? 0],
            ['Total Cost of Toolkits:', $this->formatCurrency($cost->total_cost_of_toolkits ?? 0)],
            ['Total:', $this->formatCurrency($cost->sum_of_total_amount ?? 0)],
            ['Balance:', $this->formatCurrency($cost->balance ?? 0)],
            [''],
            [''],
            [''],
        ];

        // Add column headings after the first 16 rows, starting from row 17
        return array_merge(
            $customHeadings,
            [array_values($this->columns)]
        );
    }


    public function map($record): array
    {

        // Retrieve cost data once
        $cost = $this->getCost();


        return [
            $record->municipality->province->region->name ?? '-',
            $record->municipality->province->name ?? '-',
            $record->tvi->name ?? '-',
            $record->qualification_title_name ?? '-',
            $record->number_of_slots ?? '-',


            $this->formatCurrency($cost->training_cost_pcc),
            $this->formatCurrency($cost->cost_of_toolkits_pcc),
            $this->formatCurrency($cost->total_training_cost),
            $this->formatCurrency($cost->total_cost_of_toolkits),
            $this->formatCurrency($cost->total_amount),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $columnCount = count($this->columns) + 1;
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        // Merge cells
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->mergeCells("A4:{$lastColumn}4");
        $sheet->mergeCells("A15:A16");
        $sheet->mergeCells("B15:B16");
        $sheet->mergeCells("C15:C16");
        $sheet->mergeCells("D15:D16");
        $sheet->mergeCells("E15:E16");
        $sheet->mergeCells("F15:G15");
        $sheet->mergeCells("H15:I15");
        $sheet->mergeCells("J15:J16");

        $sheet->setCellValue("A17", "a");
        $sheet->setCellValue("B17", "b");
        $sheet->setCellValue("C17", "c");
        $sheet->setCellValue("D17", "d");
        $sheet->setCellValue("E17", "e");
        $sheet->setCellValue("F17", "f");
        $sheet->setCellValue("G17", "g");
        $sheet->setCellValue("H17", "h = e*f");
        $sheet->setCellValue("I17", "i = e*g ");
        $sheet->setCellValue("J17", "j = i+h ");

        // Define header style with center alignment
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        $boldStyle = [
            'font' => ['bold' => true, 'size' => 10],
        ];

        $centerStyle = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        // Define the underline style for borders
        $underlineStyle = [
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];

        $sheet->getStyle("A17:J17")->applyFromArray($centerStyle);

        // Apply header style to relevant cells
        $sheet->getStyle("A5")->applyFromArray($boldStyle);
        $sheet->getStyle("A6")->applyFromArray($boldStyle);
        $sheet->getStyle("B6")->applyFromArray($boldStyle);
        $sheet->getStyle("A8")->applyFromArray($boldStyle);
        $sheet->getStyle("B8")->applyFromArray($boldStyle);
        $sheet->getStyle("B7")->applyFromArray($boldStyle);
        $sheet->getStyle("A12:B12")->applyFromArray($boldStyle);
        $sheet->getStyle("A13:B13")->applyFromArray($boldStyle);
        $sheet->getStyle("A15:J16")->applyFromArray($headerStyle);

        // Apply header style to other headers
        $sheet->getStyle("A1:A3")->applyFromArray($headerStyle);
        $sheet->getStyle("A6:{$lastColumn}5")->applyFromArray($headerStyle);

        // Define dynamic border styles for each individual cell
        $dynamicBorderStyle = [
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
                'left' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
                'inline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];

        // Apply bottom border to cells B7 to B13
        foreach (range(6, 13) as $row) {
            $sheet->getStyle("B$row")->applyFromArray($underlineStyle);
        }

        // Apply borders to the header row (A15:J15)
        $sheet->getStyle("A15:J15")->applyFromArray($dynamicBorderStyle);

        // Loop through rows starting from A17 to J17 and all subsequent rows
        $row = 17;
        while ($sheet->getCell("A$row")->getValue() !== null) {
            // Apply borders to each cell from A to J for the current row
            $sheet->getStyle("A$row:J$row")->applyFromArray($dynamicBorderStyle);
            $row++;
        }

        // Set column auto-size
        foreach (range(1, $columnCount) as $colIndex) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }
    }



    private function formatCurrency($amount)
    {
        $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount, 'PHP');
    }

    private function getScholarshipProgramDescription(): string
    {
        return Target::query()
            ->join('allocations', 'targets.allocation_id', '=', 'allocations.id')
            ->join('scholarship_programs', 'allocations.scholarship_program_id', '=', 'scholarship_programs.id')
            ->value('scholarship_programs.desc') ?? 'No Description';
    }


    private function getScholarshipProgramCode(): string
    {
        return Target::query()
            ->join('allocations', 'targets.allocation_id', '=', 'allocations.id')
            ->join('scholarship_programs', 'allocations.scholarship_program_id', '=', 'scholarship_programs.id')
            ->value('scholarship_programs.name') ?? 'No Code';
    }

    private function getLegislator($record)
    {
        return Target::query()
            ->join('allocations', 'targets.allocation_id', '=', 'allocations.id')
            ->join('legislators', 'allocations.legislator_id', '=', 'legislators.id')
            ->where('targets.id', $record->id ?? 0) // Handle cases where $record is null
            ->value('legislators.name') ?? 'No Legislator';
    }
    private function getAllocationYear($record)
    {
        if (!$record || !$record->id) {
            return 'No year'; // Handle null or invalid $record cases
        }

        return Target::query()
            ->join('allocations', 'targets.allocation_id', '=', 'allocations.id')
            ->where('targets.id', $record->id)
            ->value('allocations.year') ?? 'No year';
    }

    protected function getCost()
    {
        return Target::query()
            ->join('allocations', 'allocations.id', '=', 'targets.allocation_id')
            ->select([
                // TOTAL ALLOCATION
                DB::raw('allocations.allocation as total_allocation'),

                // ADMIN COST
                DB::raw('SUM(allocations.allocation * 0.02) as total_admin_cost'),

                // SUM OF TOTAL TRAINING COST
                DB::raw('SUM(targets.total_training_cost_pcc + targets.total_assessment_fee + targets.total_training_support_fund + targets.total_entrepreneurship_fee) as sum_of_total_training_cost '),

                // SUM OF TOTAL COST OF TOOLKITS
                DB::raw('SUM(targets.total_cost_of_toolkit_pcc) as sum_of_total_cost_of_toolkits '),

                // SUM OF TOTAL AMOUNT
                DB::raw('(
                    (allocations.allocation * 0.02) +
                    SUM(targets.total_training_cost_pcc + targets.total_assessment_fee + targets.total_training_support_fund + targets.total_entrepreneurship_fee) + SUM(targets.total_cost_of_toolkit_pcc)
                ) as sum_of_total_amount'),

                // BALANCE
                DB::raw('(allocations.allocation - (
                (allocations.allocation * 0.02) +
                SUM(targets.total_training_cost_pcc + targets.total_assessment_fee + targets.total_training_support_fund + targets.total_entrepreneurship_fee) +
                SUM(targets.total_cost_of_toolkit_pcc)
            )) as balance'),



                // TRAINING COST PCC
                DB::raw('(targets.total_training_cost_pcc + targets.total_assessment_fee + targets.total_training_support_fund + targets.total_entrepreneurship_fee) / targets.number_of_slots as training_cost_pcc'),

                // COST OF TOOLKITS PCC
                DB::raw('targets.total_cost_of_toolkit_pcc / targets.number_of_slots as cost_of_toolkits_pcc'),


                // TOTAL TRAINING COST
                DB::raw('targets.total_training_cost_pcc + targets.total_assessment_fee + targets.total_training_support_fund + targets.total_entrepreneurship_fee as total_training_cost'),

                // TOTAL COST OF TOOLKITS PCC
                DB::raw('targets.total_cost_of_toolkit_pcc as total_cost_of_toolkits'),

                //TOTAL AMOUNT
                DB::raw('targets.total_amount as total_amount'),

            ])
            ->first();
    }

    // private function getMunicipality($record)
    // {
    //     return Target::query()
    //         ->join('districts', 'districts.id', '=', 'targets.district_id') // Fix: Joining on districts based on district_id
    //         ->join('municipalities', 'municipalities.id', '=', 'districts.municipality_id') // Fix: Correct join to municipalities
    //         ->where('targets.id', $record->id)
    //         ->value('municipalities.name') ?? 'No Municipality'; // Fix: Reference municipality name
    // }


    protected function getParticularName(): string
    {
        // Retrieve the first Target (you can adjust this logic if needed)
        $target = Target::first();

        if (!$target) {
            abort(404, 'Target not found.');
        }

        // Access the related Allocation through the Target
        $allocation = $target->allocation;

        if (!$allocation) {
            abort(404, 'Allocation not found for the Target.');
        }

        // Access the Particular through Allocation
        $particular = $allocation->particular;

        if (!$particular) {
            abort(404, 'Particular not found for the Allocation.');
        }

        // Access SubParticular and FundSource
        $subParticular = $particular->subParticular;
        if (!$subParticular) {
            abort(404, 'Sub-Particular not found for the Particular.');
        }

        $subParticularName = $subParticular->name;
        $fundSourceName = $subParticular->fundSource->name ?? null;


        if ($fundSourceName === "RO Regular" || $fundSourceName === "CO Regular") {
            return $particular->subParticular->name . " - " . $particular->district->province->region->name;
        } elseif ($fundSourceName === "CO Legislator Funds") {
            if ($subParticularName === 'District') {
                $regionName = $particular->district->province->region->name;
                if ($regionName === 'NCR') {
                    return $particular->subParticular->name . " - " . $particular->district->underMunicipality->name;
                } else {
                    return $particular->subParticular->name . " - " . $particular->district->province->name;
                }
            }
        } elseif (in_array($subParticularName, ['House Speaker', 'House Speaker (LAKAS)'])) {
            return $subParticularName;
        }

        return 'Unknown Particular Name';
    }



    private function getProvince($record)
    {
        return Target::query()
            ->join('districts', 'districts.id', '=', 'targets.district_id') // Join districts based on district_id
            ->join('provinces', 'provinces.id', '=', 'districts.province_id') // Correctly join provinces via province_id
            ->where('targets.id', $record->id)
            ->value('provinces.name') ?? 'No Province'; // Fetch the province name
    }








}
