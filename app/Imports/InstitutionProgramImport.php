<?php

namespace App\Imports;

use App\Models\InstitutionProgram;
use App\Models\TrainingProgram;
use App\Models\Tvi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class InstitutionProgramImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {

                $institution = $this->getInstitution($row['institution']);
                $trainingProgram = $this->getTrainingProgram($row['training_program']);

                $institutionTrainingProgram = InstitutionProgram::where('tvi_id', $institution->id)
                    ->where('training_program_id', $trainingProgram->id)
                    ->exists();

                if($institutionTrainingProgram) {
                    throw new \Exception("An existing training program '{$trainingProgram->title}' is already associated with institution '{$institution->name}'.");
                }

                $institutionTrainingProgramRecord = InstitutionProgram::create([
                    'tvi_id' => $institution->id,
                    'training_program_id' => $trainingProgram->id
                ]);

                return $institutionTrainingProgramRecord;

            } catch (Throwable $e) {
                DB::rollBack();
                Log::error("An error occurred while importing row: " . json_encode($row) . " Error: " . $e->getMessage());
                throw $e;
            }
        });
    }


    protected function validateRow(array $row)
    {
        $requiredFields = ['institution', 'training_program'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function getInstitution(string $institutionName)
    {
        $institutionName = strtolower($institutionName);
        $institution = Tvi::where('name', $institutionName)->first();

        if (!$institution) {
            throw new \Exception("Institution with name '{$institutionName}' not found. No changes were saved.");
        }

        return $institution;
    }

    protected function getTrainingProgram(string $trainingProgramName)
    {
        $trainingProgramName = strtolower($trainingProgramName);
        $trainingProgram = TrainingProgram::where('title', $trainingProgramName)->first();

        if (!$trainingProgram) {
            throw new \Exception("Training Program with name '{$trainingProgramName}' not found. No changes were saved.");
        }

        return $trainingProgram;
    }
}
