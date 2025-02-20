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
        try {
            $this->validateRow($row);

            return DB::transaction(function () use ($row) {
                try {
                    $institutionName = Helper::capitalizeWords($row['institution']);
                    $institution = $this->getInstitution($institutionName);
                    $trainingPrograms = $this->getTrainingPrograms($row['training_program']); // Returns multiple records

                    if (!$institution) {
                        throw new \Exception("Institution '{$institutionName}' was not found.");
                    }

                    if ($trainingPrograms->isEmpty()) {
                        throw new \Exception("Training program '{$row['training_program']}' was not found.");
                    }

                    // Ensure valid institution ID
                    if (!isset($institution->id)) {
                        throw new \Exception("Missing necessary ID for institution.");
                    }

                    // Process each matching training program
                    $createdRecords = [];
                    foreach ($trainingPrograms as $trainingProgram) {
                        if (!isset($trainingProgram->id)) {
                            throw new \Exception("Missing necessary ID for training program.");
                        }

                        // Check if the institution-training program pair already exists
                        $exists = InstitutionProgram::where('tvi_id', $institution->id)
                            ->where('training_program_id', $trainingProgram->id)
                            ->exists();

                        if (!$exists) {
                            $createdRecords[] = InstitutionProgram::create([
                                'tvi_id' => $institution->id,
                                'training_program_id' => $trainingProgram->id
                            ]);
                        }
                    }

                    return !empty($createdRecords) ? $createdRecords : null; // Return created records or null

                } catch (Throwable $e) {
                    Log::error("Transaction failed for row: " . json_encode($row) . ". Error: " . $e->getMessage());
                    throw new \Exception("Error processing the row: " . $e->getMessage());
                }
            });

        } catch (Throwable $e) {
            Log::error("Import Error: " . $e->getMessage());
            throw new \Exception("There was an issue importing the institution qualification titles: " . $e->getMessage());
        }
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['institution', 'training_program'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty.");
            }
        }
    }

    protected function getInstitution(string $institutionName)
    {
        $institution = Tvi::where('name', $institutionName)->first();

        if (!$institution) {
            throw new \Exception("Institution with name '{$institutionName}' not found.");
        }

        return $institution;
    }

    protected function getTrainingPrograms(string $trainingProgramName)
    {
        $trainingProgramName = strtolower(trim($trainingProgramName));

        $trainingPrograms = TrainingProgram::whereRaw('LOWER(title) = ?', [$trainingProgramName])->get();

        if ($trainingPrograms->isEmpty()) {
            throw new \Exception("No matching Training Programs found for '{$trainingProgramName}'.");
        }

        return $trainingPrograms;
    }
}
