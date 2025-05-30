<?php
namespace App\Imports;

use App\Helpers\Helper;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\Toolkit;
use App\Models\TrainingProgram;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class ToolkitImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        try {
            $this->validateRow($row);

            DB::transaction(function () use ($row) {

                $lotName = Helper::capitalizeWords($row['lot_name']);
                $qualificationTitleName = Helper::capitalizeWords($row['qualification_title']);

                $toolkitRecord = Toolkit::where('lot_name', $lotName)
                    ->where('year', $row['year'])
                    ->first();

                $qualificationTitle = $this->getQualificationTitle($qualificationTitleName, $row['soc_code']);

                if (!$toolkitRecord) {
                    $toolkitRecord = Toolkit::create([
                        'lot_name' => $lotName,
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

                    activity()
                    ->causedBy(auth()->user())
                    ->performedOn($toolkitRecord)
                    ->event('Created')
                    ->withProperties([
                        'lot_name' => $toolkitRecord->lot_name,
                        'price_per_toolkit' => $toolkitRecord->price_per_toolkit ?? null,
                        'qualification_title' => $toolkitRecord->qualificationTitles->implode('trainingProgram.title', ', '),
                        'available_number_of_toolkits' => $toolkitRecord->available_number_of_toolkits,
                        'number_of_toolkits' => $toolkitRecord->number_of_toolkits,
                        'total_abc_per_lot' => $toolkitRecord->total_abc_per_lot,
                        'number_of_items_per_toolkit' => $toolkitRecord->number_of_items_per_toolkit,
                        'year' => $toolkitRecord->year,
                    ])
                    ->log("An Tookit for '{$toolkitRecord->lot_name}' has been created.");
                }

                return $toolkitRecord;
            });
        } catch (Throwable $e) {
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

    protected function getQualificationTitle(string $trainingProgramName, $socCode)
    {
        $trainingProgram = TrainingProgram::where(DB::raw('LOWER(title)'), '=', strtolower($trainingProgramName))
            ->where('soc_code', $socCode)
            ->first();

        if (!$trainingProgram) {
            throw new \Exception("Training Program with name '{$trainingProgramName}' not found.");
        }

        $stepScholarship = ScholarshipProgram::where('name', 'STEP')->first();

        if (!$stepScholarship) {
            throw new \Exception("Scholarship Program with name 'STEP' not found.");
        }

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
