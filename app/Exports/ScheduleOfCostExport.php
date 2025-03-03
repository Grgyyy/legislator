<?php

namespace App\Exports;

use App\Models\QualificationTitle;
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

class ScheduleOfCostExport implements FromQuery, WithMapping, WithStyles, WithHeadings, WithDrawings
{
    private array $columns = [
        'trainingProgram.code' => 'Qualification Code',
        'trainingProgram.soc_code' => 'SOC Code',
        'training_program_id.title' => 'Qualification Title',
        'scholarshipProgram.code' => 'Scholarship Program',
        'training_cost_pcc' => 'Training Cost PCC',
        'training_support_fund' => 'Training Support Fund',
        'assessment_fee' => 'Assessment Fee',
        'entrepreneurship_fee' => 'Entrepreneurship Fee',
        'new_normal_assistance' => 'New Normal Assistance',
        'accident_insurance' => 'Accidental Insurance',
        'book_allowance' => 'Book Allowance',
        'uniform_allowance' => 'Uniform Allowance',
        'misc_fee' => 'Miscellaneous Fee',
        'toolkits.price_per_toolkit' => 'Cost of Toolkits PCC',
        'pcc' => 'Total PCC (w/o Toolkits)',
        'days_duration' => 'Training Days',
        'hours_duration' => 'Training Hours',
        'status_id' => 'Status',
    ];

    public function query(): Builder
    {
        return QualificationTitle::query()
            ->join('training_programs', 'qualification_titles.training_program_id', '=', 'training_programs.id')
            ->orderBy('training_programs.title')
            ->whereNot('qualification_titles.soc', 0);

    }

    public function map($record): array
    {
        return [
            $record->trainingProgram->code ?? '-',
            $record->trainingProgram->soc_code ?? '-',
            $record->trainingProgram->title ?? '-',
            $record->scholarshipProgram->name ?? '-',
            $record->training_cost_pcc ?? 0, // Keep numeric
            $record->training_support_fund ?? 0,
            $record->assessment_fee ?? 0,
            $record->entrepreneurship_fee ?? 0,
            $record->new_normal_assistance ?? 0,
            $record->accident_insurance ?? 0,
            $record->book_allowance ?? 0,
            $record->uniform_allowance ?? 0,
            $record->misc_fee ?? 0,
            optional($record->toolkits->first())->price_per_toolkit ?? 0,
            $record->pcc ?? 0,
            $record->days_duration ? $record->days_duration . ' days' : '-',
            $record->hours_duration ? $record->hours_duration . ' hrs' : '-',
            $record->status->desc ?? '-',
        ];
    }


    public function headings(): array
    {
        $customHeadings = [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['SCHEDULE OF COSTS'],
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
        $tesda_logo->setHeight(80);
        $tesda_logo->setCoordinates('E1');
        $tesda_logo->setOffsetX(130);
        $tesda_logo->setOffsetY(0);

        $tuv_logo = new Drawing();
        $tuv_logo->setName('TUV Logo');
        $tuv_logo->setDescription('TUV Logo');
        $tuv_logo->setPath(public_path('images/TUV_Sud_logo.svg.png'));
        $tuv_logo->setHeight(65);
        $tuv_logo->setCoordinates('I1');
        $tuv_logo->setOffsetX(50);
        $tuv_logo->setOffsetY(8);

        return [$tesda_logo, $tuv_logo];
    }

    public function styles(Worksheet $sheet)
    {
        $columnCount = count($this->columns);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);


        $currencyColumns = ['E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];

        foreach ($currencyColumns as $col) {
            $sheet->getStyle("{$col}6:{$col}1000")
                ->getNumberFormat()
                ->setFormatCode('"₱ "#,##0.00');
        }

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
