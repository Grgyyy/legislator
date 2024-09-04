<?php

namespace App\Imports;

use App\Models\TrainingProgram;
use App\Models\ScholarshipProgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class TrainingProgramsImport implements ToModel, WithHeadingRow
{
    use Importable;

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {

        $this->validateRow($row);

        return DB::transaction(function () use ($row) {

            try {

                $scholarshipProgramId = self::getScholarshipProgramId($row['scholarship_program']);

                $trainingProgram = TrainingProgram::where('title', $row['title'])
                    ->where('code', $row['code'])
                    ->first();

                if (!$trainingProgram) {
                    $trainingProgram = TrainingProgram::create([
                        'code' => $row['code'],
                        'title' => $row['title']
                    ]);
                }

                $trainingProgram->scholarshipPrograms()->syncWithoutDetaching([$scholarshipProgramId]);

                return $trainingProgram;

            } catch (Throwable $e) {

                Log::error('Failed to import training program: ' . $e->getMessage());
                throw $e;

            }

        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['scholarship_program', 'title'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("Validation error: The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected static function getScholarshipProgramId(string $scholarshipProgramName)
    {

        $scholarshipProgram = ScholarshipProgram::where('name', $scholarshipProgramName)
            ->whereNull('deleted_at')
            ->first();

        if ($scholarshipProgram === null) {
            throw new \Exception("Scholarship program with name '{$scholarshipProgramName}' not found. No changes were saved.");
        }

        return $scholarshipProgram->id;
    }
}
