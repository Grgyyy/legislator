<?php

namespace App\Imports;

use App\Models\DeliveryMode;
use App\Models\LearningMode;
use App\Models\Particular;
use App\Models\SkillPriority;
use App\Models\SubParticular;
use App\Models\TargetStatus;
use App\Models\TrainingProgram;
use Throwable;
use App\Models\Tvi;
use App\Models\Abdd;
use App\Models\Region;
use App\Models\Target;
use App\Models\District;
use App\Models\Province;
use App\Models\Partylist;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\Municipality;
use App\Models\TargetHistory;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SkillsPriorityImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        try {
            $this->validateRow($row);
            $this->validateYear($row['year']);

            DB::transaction(function () use ($row) {
                $qualificationTitle = $this->getTrainingProgram($row['qualification_title']);
                $region = $this->getRegion($row['region']);
                $province = $this->getProvince($row['province'], $region->id);
                
                $skillPriorityExists = SkillPriority::where('training_program_id', $qualificationTitle->id)
                    ->where('province_id', $province->id)
                    ->where('year', $row['year'])
                    ->exists();

                if($skillPriorityExists) {
                    throw new \Exception("A Skill Priority under the province '{$province->name}' and qualification '{$qualificationTitle->title}' already exists.");
                }

                $skillPriority = SkillPriority::create([
                    'province_id' => $province->id,
                    'training_program_id' => $qualificationTitle->id,
                    'available_slots' => $row['no_of_skills_priorities'],
                    'total_slots' => $row['no_of_skills_priorities'],
                    'year' => $row['year'],
                ]);

                return $skillPriority;
                
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
            'province',
            'region',
            'no_of_skills_priorities',
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

    protected function getTrainingProgram(string $trainingProgramName)
    {
        $trainingProgram = TrainingProgram::where('title', $trainingProgramName)->first();

        if(!$trainingProgram) {
            throw new \Exception("Training Program with name '{$trainingProgramName}' not found.");
        }

        return $trainingProgram;
    }

    protected function getRegion(string $regionName)
    {
        $region = Region::where('name', $regionName)->first();

        if(!$region) {
            throw new \Exception("Region with name '{$regionName}' not found.");
        }

        return $region;
    }

    protected function getProvince(string $provinceName, int $regionId)
    {
        $province = Province::where('name', $provinceName)
            ->where('region_id', $regionId)
            ->first();

        if(!$province) {
            throw new \Exception("Province with name '{$provinceName}' not found.");
        }

        return $province;
    }

}
