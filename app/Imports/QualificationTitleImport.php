<?php

namespace App\Imports;

use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QualificationTitleImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        // if (!isset($row['training_program'])) {
        //     // Handle the error or skip this row
        //     return null;
        // }

        $scholarshipProgramId = $this->getScholarshipProgramId($row['scholarship_program']);
        $trainingProgramId = $this->getTrainingProgramId($row['training_program'], $row['code']);

        return new QualificationTitle([
            'code' => $row['code'],
            'title' => $row['qualification_title'],
            'training_program_id' => $trainingProgramId,
            'scholarship_program_id' => $scholarshipProgramId,
            'training_cost_pcc' => $row['training_cost_pcc'],
            'cost_of_toolkit_pcc' => $row['cost_of_toolkit_pcc'],
            'training_support_fund' => $row['training_support_fund'],
            'assessment_fee' => $row['assessment_fee'],
            'entrepeneurship_fee' => $row['entrepeneurship_fee'],
            'new_normal_assistance' => $row['new_normal_assistance'],
            'accident_insurance' => $row['accident_insurance'],
            'book_allowance' => $row['book_allowance'],
            'duration' => $row['duration'],
        ]);
    }


    private function getTrainingProgramId(string $trainingProgramName, string $code)
    {
        return TrainingProgram::where('title', $trainingProgramName && 'code', $code)
            // ->where('scholarship_program_id', $scholarshipProgramId)
            ->first()
            ->id;
    }

    private function getScholarshipProgramId(string $scholarshipProgramName)
    {
        return ScholarshipProgram::where('name', $scholarshipProgramName)
            ->first()
            ->id;
    }
}
