<?php

namespace App\Imports;

use App\Models\Allocation;
use App\Models\District;
use App\Models\Legislator;
use App\Models\Municipality;
use App\Models\Particular;
use App\Models\Partylist;
use App\Models\Province;
use App\Models\Region;
use App\Models\ScholarshipProgram;
use App\Models\SubParticular;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class AllocationImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $legislator_id = $this->getLegislatorId($row['legislator']);

                $region_id = $this->getRegionId($row['region']);
                $province_id = $this->getProvinceId($region_id, $row['province']);
                $municipality_id = $this->getMunicipalityId($province_id, $row['municipality']);
                $district_id = $this->getDistrictId($region_id, $province_id, $municipality_id, $row['district']);
                $partylist_id = $this->getPartylistId($row['particular'], $row['partylist']);
                $sub_particular_id = $this->getSubparticularId($row['particular']);
                $particular_id = $this->getParticularId($sub_particular_id, $partylist_id, $district_id);


                $schopro_id = $this->getScholarshipProgramId($row['scholarship_program']);
                $allocation = $row['allocation'];
                $admin_cost = $allocation * 0.02;

                $allocationRecord = Allocation::where('legislator_id', $legislator_id)
                    ->where('particular_id', $particular_id)
                    ->where('scholarship_program_id', $schopro_id)
                    ->where('year', $row['year'])
                    ->first();

                if (!$allocationRecord) {
                    return Allocation::create([
                        'soft_or_commitment' => $row['soft_or_commitment'],
                        'legislator_id' => $legislator_id,
                        'particular_id' => $particular_id,
                        'scholarship_program_id' => $schopro_id,
                        'allocation' => $allocation,
                        'admin_cost' => $admin_cost,
                        'balance' => $allocation - $admin_cost,
                        'year' => $row['year']
                    ]);
                } else {
                    $allocationRecord->update([
                        'allocation' => $allocation,
                        'admin_cost' => $admin_cost,
                        'balance' => $allocation - $admin_cost,
                    ]);

                    return $allocationRecord;
                }

            } catch (Throwable $e) {
                Log::error('Failed to import allocation: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['legislator', 'soft_or_commitment', 'particular', 'partylist', 'district', 'municipality', 'province', 'region', 'scholarship_program', 'allocation', 'year'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("Validation error: The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }

        if (!is_numeric($row['allocation']) || $row['allocation'] <= 0) {
            throw new \Exception("Validation error: The field 'allocation' must be a positive number. No changes were saved.");
        }

        // if (!is_numeric($row['year']) || $row['year'] < 2000 || $row['year'] < date('Y')) {
        //     throw new \Exception("Validation error: The field 'year' must be a valid year. No changes were saved.");
        // }
    }

    protected function getLegislatorId(string $legislatorName)
    {
        $legislator = Legislator::where('name', $legislatorName)
            ->first();

        if (!$legislator) {
            throw new \Exception("Legislator with name '{$legislatorName}' not found. No changes were saved.");
        }

        return $legislator->id;
    }


    protected function getRegionId($regionName)
    {
        $regionRecord = Region::where('name', $regionName)
            ->whereNull('deleted_at')
            ->first();

        if (!$regionRecord) {
            throw new \Exception("The {$regionName} region does not exist.");
        }

        return $regionRecord->id;
    }

    protected function getProvinceId($regionId, $provinceName)
    {
        $provinceRecord = Province::where('name', $provinceName)
            ->where('region_id', $regionId)
            ->whereNull('deleted_at')
            ->first();

        if (!$provinceRecord) {
            throw new \Exception("The {$provinceName} province does not exist.");
        }

        return $provinceRecord->id;
    }

    protected function getMunicipalityId($provinceId, $municipalityName)
    {
        $municipalityRecord = Municipality::where('name', $municipalityName)
            ->where('province_id', $provinceId)
            ->whereNull('deleted_at')
            ->first();

        if (!$municipalityRecord) {
            throw new \Exception("The {$municipalityName} municipality does not exist.");
        }

        return $municipalityRecord->id;
    }

    protected function getDistrictId($regionId, $provinceId, $municipalityId, $districtName)
    {
        $region = Region::find($regionId);

        if (!$region) {
            throw new \Exception("Region with ID {$regionId} does not exist.");
        }

        $query = District::where('name', $districtName)
            ->where('province_id', $provinceId);

        if ($region->name === "NCR") {
            if (empty($municipalityId)) {
                throw new \Exception("Municipality is required for districts in NCR.");
            }
            $query->where('municipality_id', $municipalityId);
        }

        $districtRecord = $query->first();

        if (!$districtRecord) {
            throw new \Exception("The district '{$districtName}' does not exist in the specified province and municipality.");
        }

        return $districtRecord->id;
    }
    protected function getSubparticularId($particularName)
    {
        $subParticular = SubParticular::where('name', $particularName)
            ->whereNull('deleted_at')
            ->first();

        if (!$subParticular) {
            throw new \Exception("The {$particularName} sub-particular does not exist.");
        }

        return $subParticular->id;
    }

    protected function getPartylistId($particularName, $partylistName)
    {
        $particularRecord = SubParticular::where('name', $particularName)
            ->whereNull('deleted_at')
            ->first();

        if (!$particularRecord) {
            throw new \Exception("The {$particularName} particular type does not exist.");
        }

        if ($particularRecord->name === 'Party-list') {
            $partylistRecord = Partylist::where('name', $partylistName)
                ->whereNull('deleted_at')
                ->first();

            if (!$partylistRecord) {
                throw new \Exception("The {$partylistName} partylist does not exist.");
            }
        } else {
            $partylistRecord = Partylist::where('name', 'Not Applicable')
                ->whereNull('deleted_at')
                ->first();

            if (!$partylistRecord) {
                throw new \Exception("The Not Applicable partylist does not exist.");
            }
        }

        return $partylistRecord->id;
    }

    protected function getParticularId($sub_particular_id, $partylist_id, $district_id)
    {
        $particularRecord = Particular::where('sub_particular_id', $sub_particular_id)
            ->where('partylist_id', $partylist_id)
            ->where('district_id', $district_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$particularRecord) {
            throw new \Exception("The Particular does not exist.");
        }

        return $particularRecord->id;
    }


    protected function getScholarshipProgramId(string $schoproName)
    {
        $scholarship = ScholarshipProgram::where('name', $schoproName)
            ->first();

        if (!$scholarship) {
            throw new \Exception("Scholarship program with name '{$schoproName}' not found. No changes were saved.");
        }

        return $scholarship->id;
    }
}
