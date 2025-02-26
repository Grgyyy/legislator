<?php

namespace App\Exports;

use App\Models\SkillPriority;
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

class SkillsPriorityExport implements FromQuery, WithMapping, WithStyles, WithHeadings, WithDrawings
{
    private array $columns = [
        'province_id' => 'Province',
        'district_id' => 'District',
        'qualification_title' => 'LOT Name',
        'training_program_title' => 'Qualification Titles',
        'available_slots' => 'Available Slots',
        'total_slots' => 'Total Slots',
        'year' => 'Year',
    ];


    public function query(): Builder
    {
        return SkillPriority::query()
            ->with('trainingProgram')
            ->select([
                'id',
                'province_id',
                'district_id',
                'qualification_title',
                'available_slots',
                'total_slots',
                'year',
            ]);
    }



    public function map($record): array
    {
        return [
            $record->provinces->name ?? '-',
            $this->getDistrict($record),
            $record->qualification_title ?? '-',
            $this->getTrainingProgram($record),
            $record->available_slots ?? 0,
            $record->total_slots ?? 0,
            $record->year ?? '-',
        ];
    }

    public function headings(): array
    {
        $customHeadings = [
            ['Technical Education And Skills Development Authority (TESDA)'],
            ['Central Office (CO)'],
            ['SKILLS PRIORITIES'],
            [''],
        ];

        return array_merge($customHeadings, [array_values($this->columns)]);
    }
    private function getDistrict($record)
    {
        if ($record->district) {
            if ($record->district->underMunicipality) {
                return $record->district->name . ' - ' . $record->district->underMunicipality->name;
            } else {
                return $record->district->name;
            }
        } else {
            return '-';
        }
    }

    private function getTrainingProgram($record)
    {

        return $record->trainingProgram->implode('title', ', ');
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('TESDA Logo');
        $drawing->setDescription('TESDA Logo');
        $drawing->setPath(public_path('images/TESDA_logo.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('C1');
        $drawing->setOffsetX(250);
        $drawing->setOffsetY(0);

        return $drawing;
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
