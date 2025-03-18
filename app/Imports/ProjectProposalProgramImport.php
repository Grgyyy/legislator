<?php

namespace App\Imports;

use App\Helpers\Helper;
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

class ProjectProposalProgramImport implements ToModel, WithHeadingRow
{
    use Importable;

    private $currentSocCode;

    public function __construct()
    {
        $this->currentSocCode = TrainingProgram::where('soc', 0)->count();
    }

    public function model(array $row)
    {
        try {
            $this->validateRow($row);

            return DB::transaction(function () use ($row) {
                $programName = Helper::capitalizeWords($row['project_proposal_program_name']);

                $projectProposalProgram = TrainingProgram::where('title', $programName)
                    ->where('soc', 1)
                    ->first();

                $tvetSector = Tvet::where('name', 'Not Applicable')->first();
                $prioSector = Priority::where('name', 'Not Applicable')->first();
                $scholarshipPrograms = ScholarshipProgram::whereIn('code', ['TTSP', 'TWSP'])->get();

                if (!$projectProposalProgram) {
                    $this->currentSocCode++;
                    $formattedSocCode = $this->formatSocCode($this->currentSocCode);

                    while (TrainingProgram::where('soc_code', $formattedSocCode)->exists()) {
                        $this->currentSocCode++;
                        $formattedSocCode = $this->formatSocCode($this->currentSocCode);
                    }

                    $existingProgram = TrainingProgram::where('title', $programName)
                        ->where('soc', 0)
                        ->first();

                    if (!$existingProgram) {
                        $projectProposalProgram = TrainingProgram::create([
                            'soc_code' => $formattedSocCode,
                            'title' => $programName,
                            'tvet_id' => $tvetSector->id,
                            'priority_id' => $prioSector->id,
                            'soc' => 0,
                        ]);
                    } else {
                        $projectProposalProgram = $existingProgram;
                    }

                    $projectProposalProgram->scholarshipPrograms()->syncWithoutDetaching(
                        $scholarshipPrograms->pluck('id')->toArray()
                    );

                    foreach ($scholarshipPrograms as $scholarshipProgram) {
                        $qualificationTitleExists = QualificationTitle::where('training_program_id', $projectProposalProgram->id)
                            ->where('scholarship_program_id', $scholarshipProgram->id)
                            ->where('soc', 0)
                            ->exists();

                        if (!$qualificationTitleExists) {
                            QualificationTitle::create([
                                'training_program_id' => $projectProposalProgram->id,
                                'scholarship_program_id' => $scholarshipProgram->id,
                                'status_id' => 1,
                                'soc' => 0,
                            ]);
                        }
                    }
                }

                return $projectProposalProgram;
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

    private function formatSocCode($currentSocCode)
    {
        if ($currentSocCode > 99999) {
            return 'PROP' . $currentSocCode;
        }

        return sprintf('PROP%06d', $currentSocCode);
    }
}
