<?php

namespace App\Imports;

use App\Models\SkillPriority;
use App\Models\Toolkit;
use App\Models\TrainingProgram;
use App\Services\NotificationHandler;
use Throwable;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ToolkitImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        try {
            $this->validateRow($row);
            
            DB::transaction(function () use ($row) {
                // Check if the toolkit already exists
                $toolkitRecord = Toolkit::where('lot_name', $row['lot_name'])
                    ->where('year', $row['year'])
                    ->first();

                $qualificationTitle = $this->getQualificationTitle($row['qualification_title'], $row['soc_code']);

                if (!$toolkitRecord) {
                    $toolkitRecord = Toolkit::create([
                        'lot_name' => $row['lot_name'],
                        'price_per_toolkit' => $row['price_per_toolkit'],
                        'available_number_of_toolkits' => $row['number_of_toolkit'] ?? null,
                        'number_of_toolkits' => $row['number_of_toolkit'] ?? null,
                        'total_abc_per_lot' => isset($row['number_of_toolkit']) 
                            ? $row['price_per_toolkit'] * $row['number_of_toolkit'] 
                            : null,
                        'number_of_items_per_toolkit' => $row['number_of_items_per_toolkit'],
                        'year' => $row['year']
                    ]);
                }

                if ($toolkitRecord->exists) {
                    $toolkitRecord->qualificationTitles()->syncWithoutDetaching([$qualificationTitle->id]);
                }

                return $toolkitRecord;
            });
        } catch (Throwable $e) {
            Log::error("Import failed: " . $e->getMessage());
            throw $e;
        }
    }

    protected function validateRow(array $row)
    {
        $requiredFields = [
            'qualification_title',
            'lot_name',
            'price_per_toolkit',
            'number_of_items_per_toolkit',
            'year',
        ];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function validateYear(int $year)
    {
        $currentYear = date('Y');
        if ($year < $currentYear) {
            throw new \Exception("The provided year '{$year}' must be the current year or a future year.");
        }
    }

    protected function getQualificationTitle(string $trainingProgramName, $socCode)
    {
        // Find the training program by title
        $trainingProgram = TrainingProgram::where(DB::raw('LOWER(title)'), '=', strtolower($trainingProgramName))
            ->where('soc_code', $socCode)
            ->first();

        if (!$trainingProgram) {
            throw new \Exception("Training Program with name '{$trainingProgramName}' not found.");
        }

        // Find the STEP Scholarship Program
        $stepScholarship = ScholarshipProgram::where('name', 'STEP')->first();

        if (!$stepScholarship) {
            throw new \Exception("Scholarship Program with name 'STEP' not found.");
        }

        // Find the Qualification Title associated with the training program and STEP scholarship
        $qualificationTitle = QualificationTitle::where('training_program_id', $trainingProgram->id)
            ->where('scholarship_program_id', $stepScholarship->id)
            ->where('status_id', 1)
            ->first();

        if (!$qualificationTitle) {
            throw new \Exception("No Qualification Title found for Training Program '{$trainingProgramName}' and Scholarship Program 'STEP'.");
        }

        return $qualificationTitle;
    }
}
