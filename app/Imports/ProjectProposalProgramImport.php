<?php

namespace App\Imports;

use App\Models\Priority;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use App\Models\Tvet;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProjectProposalProgramImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        try {
            $this->validateRow($row);

            return DB::transaction(function () use ($row) {
                $programName = $row['project_proposal_program_name'];

                $projectProposalProgram = TrainingProgram::where('title', $programName)
                    ->where('soc', 1)
                    ->first();

                $tvetSector = Tvet::where('name', $row['tvet_sector'])->first();
                $prioSector = Priority::where('name', $row['priority_sector'])->first();
                $scholarshipProgram = ScholarshipProgram::where('name', $row['scholarship_program'])->first();

                if (!$projectProposalProgram) {
                    $projectProposalProgram = TrainingProgram::create([
                        'title' => $programName,
                        'tvet_id' => $tvetSector->id,
                        'priority_id' => $prioSector->id,
                    ]);

                    $projectProposalProgram->scholarshipPrograms()->syncWithoutDetaching($scholarshipProgram);

                    $qualificationTitle = QualificationTitle::where('training_program_id', $projectProposalProgram->id)
                        ->where('scholarship_program_id', $scholarshipProgram->id)
                        ->where('soc', 0)
                        ->exists();

                    if(!$qualificationTitle) {
                        QualificationTitle::create([
                            'training_program_id' => $projectProposalProgram->id,
                            'scholarship_program_id' => $scholarshipProgram->id,
                            'status_id' => 1,
                            'soc' => 0
                        ]);
                    }
                }
                
                return $projectProposalProgram;
            });
        } catch (Throwable $e) {
            Log::error("Import failed: " . $e->getMessage());
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
