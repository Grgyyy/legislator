<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\InstitutionProgram;
use App\Models\TrainingProgram;
use App\Models\Tvi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
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
                $institutionName = Helper::capitalizeWords($row['institution']);
                $institution = $this->getInstitution($institutionName);
                $trainingPrograms = $this->getTrainingPrograms($row['training_program']);

                $createdRecords = [];

                foreach ($trainingPrograms as $trainingProgram) {
                    $exists = InstitutionProgram::where('tvi_id', $institution->id)
                        ->where('training_program_id', $trainingProgram->id)
                        ->exists();

                    if (!$exists) {
                        $institutionTrainingProgramRecord = InstitutionProgram::create([
                            'tvi_id' => $institution->id,
                            'training_program_id' => $trainingProgram->id
                        ]);

                        $createdRecords[] = $institutionTrainingProgramRecord;
                    }
                }

                return count($createdRecords) > 0 ? $createdRecords : null;

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
        $institution = Tvi::where('name', $institutionName)->first();

        if (!$institution) {
            throw new \Exception("Institution with name '{$institutionName}' not found. No changes were saved.");
        }

        return $institution;
    }

    protected function getTrainingPrograms(string $trainingProgramNames)
    {
        $trainingProgramNames = array_map('trim', explode(',', $trainingProgramNames));

        $trainingPrograms = TrainingProgram::whereIn(DB::raw('LOWER(title)'), $trainingProgramNames)->get();

        if ($trainingPrograms->isEmpty()) {
            throw new \Exception("No matching Training Programs found for '{$trainingProgramNames}'. No changes were saved.");
        }

        return $trainingPrograms;
    }
}
