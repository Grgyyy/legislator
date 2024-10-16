<?php

namespace App\Imports;

use App\Models\Legislator;
use App\Models\Particular;
use App\Models\District;
use App\Models\Municipality;
use App\Models\Partylist;
use App\Models\Province;
use App\Models\Region;
use App\Models\SubParticular;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;

class LegislatorImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row) {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $region_id = $this->getRegionId($row['region']);
                $province_id = $this->getProvinceId($region_id, $row['province']);
                $municipality_id = $this->getMunicipalityId($province_id, $row['municipality']);
                $district_id = $this->getDistrictId($municipality_id, $row['district']);
                $partylist_id = $this->getPartylistId($row['particular'], $row['partylist']);
                $sub_particular_id = $this->getSubparticularId($row['particular']);
                $particular_id = $this->getParticularId($sub_particular_id, $partylist_id, $district_id);

                $legislator = Legislator::firstOrCreate(['name' => $row['legislator']]);

                if (!$legislator->particular()->where('particular_id', $particular_id)->exists()) {
                    $legislator->particular()->attach($particular_id);
                }

            } catch (Throwable $e) {
                Log::error('Failed to import legislators: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    protected function validateRow(array $row) {
        $requiredFields = ['legislator', 'particular', 'district', 'municipality', 'province', 'region'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function getRegionId($regionName) {
        $regionRecord = Region::where('name', $regionName)
            ->whereNull('deleted_at')
            ->first();

        if (!$regionRecord) {
            throw new \Exception("The {$regionName} region does not exist.");
        }

        return $regionRecord->id;
    }

    protected function getProvinceId($regionId, $provinceName) {
        $provinceRecord = Province::where('name', $provinceName)
            ->where('region_id', $regionId)
            ->whereNull('deleted_at')
            ->first();

        if (!$provinceRecord) {
            throw new \Exception("The {$provinceName} province does not exist.");
        }

        return $provinceRecord->id;
    }

    protected function getMunicipalityId($provinceId, $municipalityName) {
        $municipalityRecord = Municipality::where('name', $municipalityName)
            ->where('province_id', $provinceId)
            ->whereNull('deleted_at')
            ->first();

        if (!$municipalityRecord) {
            throw new \Exception("The {$municipalityName} municipality does not exist.");
        }

        return $municipalityRecord->id;
    }

    protected function getDistrictId($municipalityId, $districtName) {
        $districtRecord = District::where('name', $districtName)
            ->where('municipality_id', $municipalityId)
            ->whereNull('deleted_at')
            ->first();

        if (!$districtRecord) {
            throw new \Exception("The {$districtName} district does not exist."); 
        }

        return $districtRecord->id;
    }

    protected function getSubparticularId($particularName) {
        $subParticular = SubParticular::where('name', $particularName)
            ->whereNull('deleted_at')
            ->first();

        if (!$subParticular) {
            throw new \Exception("The {$particularName} sub-particular does not exist."); 
        }

        return $subParticular->id;
    }

    protected function getPartylistId($particularName, $partylistName) {
        $particularRecord = SubParticular::where('name', $particularName)
            ->whereNull('deleted_at')
            ->first();

        if (!$particularRecord) {
            throw new \Exception("The {$particularName} particular type does not exist."); 
        }

        if($particularRecord->name === 'Party-list') {
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

    protected function getParticularId($sub_particular_id, $partylist_id, $district_id) {
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
}
