<?php

namespace App\Imports;

use App\Models\QualificationTitle;
use App\Models\TrainingProgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
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
                $trainingProgramId = $this->getTrainingProgramId($row['training_program'], $row['soc_code']);
                $scholarshipProgramId = $this->getScholarshipProgramId($row['scholarship_program'], $trainingProgramId);

                $costs = [
                    'training_cost_pcc' => isset($row['training_cost_pcc']) ? (float) $row['training_cost_pcc'] : 0,
                    'training_support_fund' => isset($row['training_support_fund']) ? (float) $row['training_support_fund'] : 0,
                    'assessment_fee' => isset($row['assessment_fee']) ? (float) $row['assessment_fee'] : 0,
                    'entrepreneurship_fee' => isset($row['entrepreneurship_fee']) ? (float) $row['entrepreneurship_fee'] : 0,
                    'new_normal_assistance' => isset($row['new_normal_assistance']) ? (float) $row['new_normal_assistance'] : 0,
                    'accident_insurance' => isset($row['accident_insurance']) ? (float) $row['accident_insurance'] : 0,
                    'book_allowance' => isset($row['book_allowance']) ? (float) $row['book_allowance'] : 0,
                    'uniform_allowance' => isset($row['uniform_allowance']) ? (float) $row['uniform_allowance'] : 0,
                    'misc_fee' => isset($row['miscellaneous_fee']) ? (float) $row['miscellaneous_fee'] : 0,
                ];

                $totalPCC = array_sum($costs);

                $qualificationTitle = QualificationTitle::where('training_program_id', $trainingProgramId)
                    ->where('scholarship_program_id', $scholarshipProgramId)
                    ->first();

                if (!$qualificationTitle) {
                    $qualificationTitle = QualificationTitle::create(array_merge($costs, [
                        'training_program_id' => $trainingProgramId,
                        'scholarship_program_id' => $scholarshipProgramId,
                        'hours_duration' => isset($row['no_of_training_hours']) ? (float) $row['no_of_training_hours'] : 0,
                        'days_duration' => isset($row['no_of_training_days']) ? (float) $row['no_of_training_days'] : 0,
                        'pcc' => $totalPCC,
                        'soc' => 1
                    ]));
                } else {
                    $qualificationTitle->update(array_merge($costs, [
                        'hours_duration' => isset($row['no_of_training_hours']) ? (float) $row['no_of_training_hours'] : 0,
                        'days_duration' => isset($row['no_of_training_days']) ? (float) $row['no_of_training_days'] : 0,
                        'pcc' => $totalPCC,
                        'soc' => 1
                    ]));
                }

                return $qualificationTitle;
            } catch (Throwable $e) {
                Log::error('Failed to import Qualification Title: ' . $e->getMessage());
                
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['soc_code', 'training_program', 'scholarship_program', 'training_cost_pcc', 'no_of_training_hours', 'no_of_training_days'];

        foreach ($requiredFields as $field) {
            if (!isset($row[$field]) || trim($row[$field]) === '') {
                throw new \Exception("Validation error: The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function getTrainingProgramId(string $trainingProgramName, string $socCode): int
    {
        $trainingProgram = TrainingProgram::where(DB::raw('LOWER(title)'), strtolower($trainingProgramName))
            ->where('soc_code', $socCode)
            ->first();

        if (!$trainingProgram) {
            throw new \Exception("Training program with name '{$trainingProgramName}' not found. No changes were saved.");
        }

        return $trainingProgram->id;
    }

    protected function getScholarshipProgramId(string $scholarshipProgramName, int $trainingProgramId): int
    {
        $trainingProgram = TrainingProgram::find($trainingProgramId);

        if (!$trainingProgram) {
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