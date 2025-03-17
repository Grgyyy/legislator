<?php

namespace App\Imports;

use App\Models\Priority;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use App\Models\Tvet;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class AdminProjectProposalImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        try {
            $this->validateRow($row);

            return DB::transaction(function () use ($row) {
                $programName = strtolower($row['program_name']);

                if (!TrainingProgram::where('title', $programName)->exists()) {

                    $tvetSector = Tvet::where('name', 'Not Applicable')->first();
                    $prioSector = Priority::where('name', 'Not Applicable')->first();

                    $trainingProgramRecord = TrainingProgram::create([
                        'title' => $programName,
                        'tvet_id' => $tvetSector->id,
                        'priority_id' => $prioSector->id,
                    ]);

                    $scholarshipPrograms = ScholarshipProgram::all();

                    $trainingProgramRecord->scholarshipPrograms()->syncWithoutDetaching(
                        $scholarshipPrograms->pluck('id')->toArray()
                    );

                    foreach ($scholarshipPrograms as $scholarshipProgram) {
                        QualificationTitle::create([
                            'training_program_id' => $trainingProgramRecord->id,
                            'scholarship_program_id' => $scholarshipProgram->id,
                            'soc' => 0,
                        ]);
                    }

                    return $trainingProgramRecord;
                }

                return null;
            });
        } catch (Throwable $e) {
            throw $e;
        }
    }

    protected function validateRow(array $row)
    {
        $requiredFields = [
            'project_proposal_program_name',
        ];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }
}
