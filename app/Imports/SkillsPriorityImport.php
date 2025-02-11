<?php

namespace App\Imports;

use App\Models\Municipality;
use App\Models\SkillPriority;
use App\Models\Status;
use App\Models\TrainingProgram;
use App\Models\Region;
use App\Models\Province;
use App\Models\District;
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
            $this->validateYear((int) $row['year']);

            DB::transaction(function () use ($row) {
                $qualificationTitle = $this->getTrainingProgram($row['qualification_title'], $row['soc_code']);
                $region = $this->getRegion($row['region']);
                $province = $this->getProvince($row['province'], $region->id);
                $municipality = $this->getMunicipality($row['municipality'] ?? null, $province);
                $district = $this->getDistrict($row['district'] ?? null, $province, $municipality);

                $status = Status::where('desc', 'Active')->first();

                if (!$status) {
                    throw new \Exception("The Active status does not exist.");
                }

                $skillPriority = SkillPriority::firstOrNew([
                    'province_id' => $province->id,
                    'district_id' => $district?->id,
                    'qualification_title' => $row['lot_name'],
                    'year' => $row['year'],
                ]);

                if ($skillPriority->exists && $skillPriority->total_slots !== (int) $row['target_benificiaries']) {
                    throw new \Exception("Skill Priority already exists and the target beneficiaries do not match the record.");
                }

                $skillPriority->available_slots = $skillPriority->exists ? $skillPriority->available_slots : (int) $row['target_benificiaries'];
                $skillPriority->total_slots = (int) $row['target_benificiaries'];
                $skillPriority->status_id = $status->id;
                $skillPriority->save();

                $skillPriority->trainingProgram()->syncWithoutDetaching([$qualificationTitle->id]);

                return $skillPriority;
            });
        } catch (\Throwable $e) {
            Log::error("Import failed: " . $e->getMessage());
            throw $e;
        }
    }

    protected function validateRow(array $row)
    {
        $requiredFields = [
            'lot_name',
            'soc_code',
            'qualification_title',
            'province',
            'region',
            'target_benificiaries',
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
        $currentYear = (int) date('Y');
        if ($year < $currentYear) {
            throw new \Exception("The provided year '{$year}' must be the current year or a future year.");
        }
    }

    protected function getTrainingProgram(string $trainingProgramName, string $socCode)
    {
        $trainingProgram = TrainingProgram::where('title', $trainingProgramName)
            ->where('soc_code', $socCode)
            ->first();

        if (!$trainingProgram) {
            throw new \Exception("Qualification Title with name '{$trainingProgramName}' not found.");
        }

        return $trainingProgram;
    }

    protected function getRegion(string $regionName)
    {
        $region = Region::where('name', $regionName)->first();

        if (!$region) {
            throw new \Exception("Region with name '{$regionName}' not found.");
        }

        return $region;
    }

    protected function getProvince(string $provinceName, int $regionId)
    {
        $province = Province::where('name', $provinceName)
            ->where('region_id', $regionId)
            ->first();

        if (!$province) {
            throw new \Exception("Province with name '{$provinceName}' not found.");
        }

        return $province;
    }

    protected function getDistrict(?string $districtName, Province $province, ?Municipality $municipality)
    {
        if (!$districtName) {
            return null;
        }

        $districtQuery = District::where('name', $districtName)
            ->where('province_id', $province->id);

        if ($municipality) {
            $districtQuery->where('municipality_id', $municipality->id);
        }

        $district = $districtQuery->first();

        if (!$district) {
            throw new \Exception("District with name '{$districtName}' under the province '{$province->name}' not found.");
        }

        return $district;
    }

    protected function getMunicipality(?string $municipalityName, Province $province)
    {
        if (!$municipalityName) {
            return null;
        }

        $municipality = Municipality::where('name', $municipalityName)
            ->where('province_id', $province->id)
            ->first();

        if (!$municipality) {
            throw new \Exception("Municipality with name '{$municipalityName}' under the province '{$province->name}' not found.");
        }

        return $municipality;
    }
}