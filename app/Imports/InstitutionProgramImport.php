<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\InstitutionProgram;
use App\Models\TrainingProgram;
use App\Models\Tvi;
use Illuminate\Support\Facades\DB;
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
                    $institution = $this->getInstitution($institutionName, $row['full_address']);
                    $trainingPrograms = $this->getTrainingPrograms($row['qualification_title']);

                    if (!$institution) {
                        throw new \Exception("Institution '{$institutionName}' was not found.");
                    }

                    if ($trainingPrograms->isEmpty()) {
                        throw new \Exception("Training program '{$row['qualification_title']}' was not found.");
                    }

                    if (!isset($institution->id)) {
                        throw new \Exception("Missing necessary ID for institution.");
                    }

                    $createdRecords = [];
                    foreach ($trainingPrograms as $trainingProgram) {
                        if (!isset($trainingProgram->id)) {
                            throw new \Exception("Missing necessary ID for training program.");
                        }

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

                    return !empty($createdRecords) ? $createdRecords : null;

                } catch (Throwable $e) {
                    throw new \Exception("Error processing the row: " . $e->getMessage());
                }
            });

        } catch (Throwable $e) {
            throw new \Exception("There was an issue importing the institution qualification titles: " . $e->getMessage());
        }
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['institution', 'qualification_title'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty.");
            }
        }
    }

    protected function getInstitution(string $institutionName, string $fullAddress)
    {
        $institution = Tvi::where('name', $institutionName)
            ->where('address', $fullAddress)
            ->first();

        if (!$institution) {
            throw new \Exception("Institution with name '{$institutionName}' and address of '{$fullAddress}' not found.");
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
