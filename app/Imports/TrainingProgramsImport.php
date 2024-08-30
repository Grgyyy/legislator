<?php

namespace App\Imports;

use App\Models\TrainingProgram;
use App\Models\ScholarshipProgram;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TrainingProgramsImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $validator = Validator::make($row, [
            'code' => 'required|int',
            'title' => 'required|string',
            'scholarship_program' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("Validation failed for row: " . json_encode($row) . " with errors: " . json_encode($validator->errors()->all()));
            return null;
        }

        $code = $row['code'];
        $title = $row['title'];
        $scholarshipProgramName = $row['scholarship_program'];

        $scholarshipProgram = ScholarshipProgram::firstOrCreate(['name' => $scholarshipProgramName]);

        $trainingProgram = TrainingProgram::firstOrCreate(
            ['code' => $code],
            ['title' => $title]
        );

        // Associate the training program with the scholarship program
        $trainingProgram->scholarshipPrograms()->syncWithoutDetaching([$scholarshipProgram->id]);

        return $trainingProgram;
    }
}
