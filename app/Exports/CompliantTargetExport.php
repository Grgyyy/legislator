<?php

namespace App\Exports;

use App\Models\Target;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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

class CompliantTargetExport implements FromQuery, WithHeadings, WithStyles, WithMapping, WithDrawings, WithColumnWidths
{
    private array $columns = [
        'fund_source' => 'Fund Source',
        'allocation.soft_or_commitment' => 'Source of Fund',
        'attributionAllocation.legislator.name' => 'Attributor',
        'attributionAllocation.legislator.particular.subParticular' => 'Attribution Particular',
        'allocation.legislator.name' => 'Legislator',
        'allocation.legislator.particular.subParticular' => 'Particular',
        'appropriation_type' => 'Appropriation Type',
        'allocation.year' => 'Appropriation Year',

        'tvi.school_id' => 'School ID',
        'institution_name' => 'Institution',
        'institution_type' => 'Institution Type',
        'institution_class' => 'Institution Class',

        'district_name' => 'District',
        'municipality_name' => 'Municipality',
        'municipality.province.name' => 'Province',
        'region_name' => 'Region',

        'qualification_title_code' => 'SOC Code',
        'qualification_title_name' => 'Qualification Title',
        'qualification_title.scholarshipProgram.name' => 'Scholarship Program',

        'abdd.name' => 'ABDD Sector',
        'qualification_title.trainingProgram.tvet.name' => 'TVET Sector',
        'qualification_title.trainingProgram.priority.name' => 'Priority Sector',

        'deliveryMode.name' => 'Delivery Mode',
        'learningMode.name' => 'Learning Mode',

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

    public function query(): Builder
    {
        $user = request()->user();
        $query = Target::query()
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
                'qualification_title_soc_code',
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
            ->with([
                'attributionAllocation.legislator.particular.subParticular',
                'allocation.legislator.particular.subParticular',
                'municipality.province.region',
                'tvi.tviType',
                'qualification_title.trainingProgram.tvet',
                'qualification_title.trainingProgram.priority',
                'allocation.scholarship_program',
            ])
            ->where('target_status_id', 2);

        if ($user) {
            $userRegionIds = $user->region()->pluck('regions.id')->toArray();
            $userProvinceIds = $user->province()->pluck('provinces.id')->toArray();
            $userDistrictIds = $user->district()->pluck('districts.id')->toArray();
            $userMunicipalityIds = $user->municipality()->pluck('municipalities.id')->toArray();

            $isPO_DO = !empty($userProvinceIds) || !empty($userMunicipalityIds) || !empty($userDistrictIds);
            $isRO = !empty($userRegionIds);

            if ($isPO_DO) {
                $query->where(function ($q) use ($userProvinceIds, $userDistrictIds, $userMunicipalityIds) {
                    if (!empty($userDistrictIds) && !empty($userMunicipalityIds)) {
                        $q->whereHas('district', function ($districtQuery) use ($userDistrictIds) {
                            $districtQuery->whereIn('districts.id', $userDistrictIds);
                        })->whereHas('municipality', function ($municipalityQuery) use ($userMunicipalityIds) {
                            $municipalityQuery->whereIn('municipalities.id', $userMunicipalityIds);
                        });
                    } elseif (!empty($userMunicipalityIds)) {
                        $q->whereHas('municipality', function ($municipalityQuery) use ($userMunicipalityIds) {
                            $municipalityQuery->whereIn('municipalities.id', $userMunicipalityIds);
                        });
                    } elseif (!empty($userDistrictIds)) {
                        $q->whereHas('district', function ($districtQuery) use ($userDistrictIds) {
                            $districtQuery->whereIn('districts.id', $userDistrictIds);
                        });
                    } elseif (!empty($userProvinceIds)) {
                        $q->whereHas('district.province', function ($districtQuery) use ($userProvinceIds) {
                            $districtQuery->whereIn('province_id', $userProvinceIds);
                        });

                        $q->orWhereHas('municipality.province', function ($municipalityQuery) use ($userProvinceIds) {
                            $municipalityQuery->whereIn('province_id', $userProvinceIds);
                        });
                    }
                });
            }

            if ($isRO) {
                $query->where(function ($q) use ($userRegionIds) {
                    $q->orWhereHas('district.province', function ($provinceQuery) use ($userRegionIds) {
                        $provinceQuery->whereIn('region_id', $userRegionIds);
                    });

                    $q->orWhereHas('municipality.province', function ($provinceQuery) use ($userRegionIds) {
                        $provinceQuery->whereIn('region_id', $userRegionIds);
                    });
                });
            }
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['COMPLIANT TARGETS'],
            [''],
            array_values($this->columns),
        ];
    }

    public function map($record): array
    {
        return array_merge([
            $this->getFundSource($record),
            $record->allocation->soft_or_commitment ?? '-',
            $this->attributionSender($record),
            $this->attributionParticular($record),
            $this->getLegislator($record->allocation),
            $this->getParticular($record),
            $record->appropriation_type,
            $record->allocation->year,

            $record->tvi->school_id ?? '-',
            $record->tvi->name ?? '-',
            $record->tvi->tviType->name ?? '-',
            $record->tvi->tviClass->name ?? '-',

            $record->district->name ?? '-',
            $record->municipality->name ?? '-',
            $record->municipality->province->name ?? '-',
            $record->municipality->province->region->name ?? '-',

            $record->qualification_title_soc_code ?? '-',
            $record->qualification_title_name ?? '-',


            $record->qualification_title->scholarshipProgram->name ?? '-',

            $record->abdd->name ?? '-',
            $record->qualification_title->trainingProgram->tvet->name ?? '-',
            $record->qualification_title->trainingProgram->priority->name ?? '-',

            $record->deliveryMode->name ?? '-',
            $record->learningMode->name ?? '-',

            $record->number_of_slots,
            $this->calculateCostPerSlot($record, 'total_training_cost_pcc'),
            $this->calculateCostPerSlot($record, 'total_cost_of_toolkit_pcc'),
            $this->calculateCostPerSlot($record, 'total_training_support_fund'),
            $this->calculateCostPerSlot($record, 'total_assessment_fee'),
            $this->calculateCostPerSlot($record, 'total_entrepreneurship_fee'),
            $this->calculateCostPerSlot($record, 'total_new_normal_assisstance'),
            $this->calculateCostPerSlot($record, 'total_accident_insurance'),
            $this->calculateCostPerSlot($record, 'total_book_allowance'),
            $this->calculateCostPerSlot($record, 'total_uniform_allowance'),
            $this->calculateCostPerSlot($record, 'total_misc_fee'),
            $this->calculateCostPerSlot($record, 'total_amount'),

            $record->total_training_cost_pcc ?? 0,
            $record->total_cost_of_toolkit_pcc ?? 0,
            $record->total_training_support_fund ?? 0,
            $record->total_assessment_fee ?? 0,
            $record->total_entrepreneurship_fee ?? 0,
            $record->total_new_normal_assisstance ?? 0,
            $record->total_accident_insurance ?? 0,
            $record->total_book_allowance ?? 0,
            $record->total_uniform_allowance ?? 0,
            $record->total_misc_fee ?? 0,
            $record->total_amount ?? 0,

            $record->targetStatus->desc ?? '-',
        ]);
    }

    private function getFundSource($record): string
    {
        return $record->allocation->particular->subParticular->fundSource->name ?? 'No fund source available';
    }
    private function getQualificationTitle($record)
    {
        $qualificationCode = $record->qualification_title_soc_code ?? '';
        $qualificationName = $record->qualification_title_name ?? '';

        return "{$qualificationCode} - {$qualificationName}";
    }

    private function attributionSender($record)
    {
        return $record->allocation->attributor ? $record->allocation->attributor->name : '-';
    }

    private function attributionParticular($record)
    {
        $particular = $record->allocation->attributorParticular;

        if (!$particular) {
            return '-';
        }

        $district = $particular->district;
        $districtName = $district ? $district->name : '';

        if ($districtName === 'Not Applicable') {
            if ($particular->subParticular && $particular->subParticular->name === 'Party-list') {
                return "{$particular->subParticular->name} - {$particular->partylist->name}";
            } else {
                return $particular->subParticular->name ?? '-';
            }
        } else {
            if ($particular->district->underMunicipality) {
                return "{$particular->subParticular->name} - {$districtName}, {$district->underMunicipality->name}, {$district->province->name}";
            } else {
                return "{$particular->subParticular->name} - {$districtName}, {$district->province->name}";
            }
        }
    }
    private function getLegislator($allocation)
    {
        return $allocation->legislator->name ?? '-';
    }
    private function getParticular($record)
    {
        $particulars = $record->allocation->legislator->particular;
        return $particulars->isNotEmpty() ? ($particulars->first()->subParticular->name ?? '-') : '-';
    }
    private function calculateCostPerSlot($record, $costColumn)
    {
        $cost = $record->$costColumn ?? 0;
        return ($record->number_of_slots > 0) ? number_format($cost / $record->number_of_slots, 2, '.', '') : '0.00';
    }
    private function calculatePerSlot(string $field)
    {
        return DB::raw("CASE WHEN number_of_slots = 0 THEN NULL ELSE {$field} / number_of_slots END");
    }

    public function drawings()
    {
        $tesda_logo = new Drawing();
        $tesda_logo->setName('TESDA Logo');
        $tesda_logo->setDescription('TESDA Logo');
        $tesda_logo->setPath(public_path('images/TESDA_logo.png'));
        $tesda_logo->setHeight(70);
        $tesda_logo->setCoordinates('W1');
        $tesda_logo->setOffsetX(-80);
        $tesda_logo->setOffsetY(0);

        $tuv_logo = new Drawing();
        $tuv_logo->setName('TUV Logo');
        $tuv_logo->setDescription('TUV Logo');
        $tuv_logo->setPath(public_path('images/TUV_Sud_logo.svg.png'));
        $tuv_logo->setHeight(55);
        $tuv_logo->setCoordinates('Y1');
        $tuv_logo->setOffsetX(20);
        $tuv_logo->setOffsetY(8);

        return [$tesda_logo, $tuv_logo];
    }


    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 30,
            'D' => 40,
            'E' => 30,
            'F' => 40,
            'G' => 20,
            'H' => 20,
            'I' => 15,
            'J' => 50,
            'K' => 20,
            'L' => 20,
            'M' => 20,
            'N' => 20,
            'O' => 20,
            'P' => 20,
            'Q' => 20,
            'R' => 40,
            'S' => 20,
            'T' => 40,
            'U' => 40,
            'V' => 40,
            'W' => 40,
            'X' => 40,
            'Y' => 20,
            'Z' => 20,
            'AA' => 20,
            'AB' => 25,
            'AC' => 20,
            'AD' => 25,
            'AE' => 25,
            'AF' => 20,
            'AG' => 20,
            'AH' => 20,
            'AI' => 20,
            'AJ' => 20,
            'AK' => 30,
            'AL' => 30,
            'AM' => 30,
            'AN' => 30,
            'AO' => 30,
            'AP' => 30,
            'AQ' => 30,
            'AR' => 30,
            'AS' => 30,
            'AT' => 30,
            'AU' => 30,
            'AV' => 20,
        ];
    }


    public function styles(Worksheet $sheet)
    {
        $columnCount = count($this->columns);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        $startColumnIndex = Coordinate::columnIndexFromString('Z');
        $endColumnIndex = Coordinate::columnIndexFromString('AU');

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
