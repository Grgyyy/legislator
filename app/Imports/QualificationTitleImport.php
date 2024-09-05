<?php

namespace App\Imports;

use App\Models\QualificationTitle;
use App\Models\TrainingProgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class QualificationTitleImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $trainingProgramId = $this->getTrainingProgramId($row['training_program']);
                $scholarshipProgramId = $this->getScholarshipProgramId($row['scholarship_program'], $trainingProgramId);

                $qualificationTitleIsRecord = QualificationTitle::where('training_program_id', $trainingProgramId)
                                                                ->where('scholarship_program_id', $scholarshipProgramId)
                                                                ->first();
                $costOfToolkit = $row['cost_of_toolkit_pcc'] === null ? 0 : $row['cost_of_toolkit_pcc'];
                $trainingSupportFund = $row['training_support_fund'] === null ? 0 : $row['training_support_fund'];
                $assessmentFee = $row['assessment_fee'] === null ? 0 : $row['assessment_fee'];
                $entrepeneurFee = $row['entrepeneurship_fee'] === null ? 0 : $row['entrepeneurship_fee'];
                $newNormalAssistance = $row['new_normal_assistance'] === null ? 0 : $row['new_normal_assistance'];
                $accidentInsurance = $row['accident_insurance'] === null ? 0 : $row['accident_insurance'];
                $bookAllowance = $row['book_allowance'] === null ? 0 : $row['book_allowance'];
                $unifAllowance = $row['uniform_allowance'] === null ? 0 : $row['uniform_allowance'];
                $miscFee = $row['miscellaneous_fee'] === null ? 0 : $row['miscellaneous_fee'];

                if(!$qualificationTitleIsRecord) {
                    return QualificationTitle::create([
                        'training_program_id' => $trainingProgramId,
                        'scholarship_program_id' => $scholarshipProgramId,
                        'training_cost_pcc' => $row['training_cost_pcc'],
                        'cost_of_toolkit_pcc' => $costOfToolkit,
                        'training_support_fund' => $trainingSupportFund,
                        'assessment_fee' => $assessmentFee,
                        'entrepeneurship_fee' => $entrepeneurFee,
                        'new_normal_assisstance' => $newNormalAssistance,
                        'accident_insurance' => $accidentInsurance,
                        'book_allowance' => $bookAllowance,
                        'uniform_allowance' => $unifAllowance,
                        'misc_fee' => $miscFee,
                        'hours_duration' => $row['no_of_training_hours'],
                        'days_duration' => $row['no_of_training_days'],
                    ]);
                }
                else {
                    $qualificationTitleIsRecord->update([
                        'training_cost_pcc' => $row['training_cost_pcc'],
                        'cost_of_toolkit_pcc' => $costOfToolkit,
                        'training_support_fund' => $trainingSupportFund,
                        'assessment_fee' => $assessmentFee,
                        'entrepeneurship_fee' => $entrepeneurFee,
                        'new_normal_assisstance' => $newNormalAssistance,
                        'accident_insurance' => $accidentInsurance,
                        'book_allowance' => $bookAllowance,
                        'uniform_allowance' => $unifAllowance,
                        'misc_fee' => $miscFee,
                        'hours_duration' => $row['no_of_training_hours'],
                        'days_duration' => $row['no_of_training_days'],
                    ]);

                    return $qualificationTitleIsRecord;
                }
            }

            catch (Throwable $e) {
                Log::error('Failed to import Qualificaiton Title: ' . $e->getMessage());
                throw $e;
            }
        });
    }


    protected function validateRow(array $row)
    {
        $requiredFields = ['training_program', 'scholarship_program', 'training_cost_pcc', 'no_of_training_hours', 'no_of_training_days'];

        foreach ($requiredFields as $field) {
            if (!isset($row[$field]) || trim($row[$field]) === '') {
                throw new \Exception("Validation error: The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function getTrainingProgramId(string $trainingProgramName)
    {
        $trainingProgram = TrainingProgram::where('title', $trainingProgramName)
            ->first();

        if (!$trainingProgram) {
                throw new \Exception("Training program with name '{$trainingProgramName}' not found. No changes were saved.");
            }

        return $trainingProgram->id;
    }

    protected function getScholarshipProgramId(string $scholarshipProgramName, int $trainingProgramId)
    {
        $trainingProgram = TrainingProgram::find($trainingProgramId);

        if (!$trainingProgram) {
            // Handle the case where the training program is not found
            throw new \Exception('Training program not found. No changes were saved.');
        }

        $scholarshipPrograms = $trainingProgram->scholarshipPrograms;

        foreach ($scholarshipPrograms as $scholarshipProgram) {
            if ($scholarshipProgram->name === $scholarshipProgramName) {
                return $scholarshipProgram->id;
            }
        }

        throw new \Exception("Scholarship program named '$scholarshipProgramName' not found. No changes were saved.");
    }
}
