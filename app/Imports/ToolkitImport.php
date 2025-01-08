<?php

namespace App\Imports;


use App\Models\SkillPriority;
use App\Models\Toolkit;
use App\Models\TrainingProgram;
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
            $this->validateYear($row['year']);

            DB::transaction(function () use ($row) {
                $qualificationTitle = $this->getQualificationTitle($row['qualification_title']);
                
                $toolkitExists = Toolkit::where('qualification_title_id', $qualificationTitle->id)
                    ->where('year', $row['year'])
                    ->exists();
                
                if($toolkitExists) {
                    throw new \Exception("A Toolkit is already linked with the Qualification Title named '{$qualificationTitle->trainingProgram->title}'.");
                }

                $toolkit = Toolkit::create([
                    'qualification_title_id' => $qualificationTitle->id,
                    'price_per_toolkit' => $row['price_per_toolkit'],
                    'number_of_toolkit' => $row['number_of_toolkit'],
                    'available_number_of_toolkit' => $row['number_of_toolkit'],
                    'total_abc_per_lot' => $row['number_of_toolkit'] *  $row['price_per_toolkit'],
                    'number_of_items_per_toolkit' => $row['number_of_items_per_toolkit'],
                    'year' => $row['year'],
                ]);

                return $toolkit;
                
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
            'price_per_toolkit',
            'number_of_toolkit',
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

    protected function getQualificationTitle(string $trainingProgramName)
    {
        $trainingProgram = TrainingProgram::where(DB::raw('LOWER(title)'), '=', strtolower($trainingProgramName))->first();

        if(!$trainingProgram) {
            throw new \Exception("Training Program with name '{$trainingProgramName}' not found.");
        }

        $stepScholarship = ScholarshipProgram::where('name', 'STEP')->first();

        if(!$stepScholarship) {
            throw new \Exception("Scholarship Program with name 'STEP' not found.");
        }

        $qualificationTitle = QualificationTitle::where('training_program_id', $trainingProgram->id)
            ->where('scholarship_program_id', $stepScholarship->id)
            ->where('soc', 1)
            ->where('status_id', 1)
            ->first();

        if(!$qualificationTitle) {
            throw new \Exception("The Qualification Title '{$qualificationTitle->id}' with the Scholarship Program 'STEP' could not be found.");
        }
    
        return $qualificationTitle;
    }

}
