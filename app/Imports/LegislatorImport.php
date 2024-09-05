<?php

namespace App\Imports;

use App\Models\Legislator;
use App\Models\Particular;
use App\Models\District;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class LegislatorImport implements ToModel, WithHeadingRow
{
    use Importable;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */

     public function model(array $row) {
        $this->validateRow($row); 
    
        return DB::transaction(function () use ($row) {
            try {
                $region_id = $this->getRegionId($row['region']);
                $province_id = $this->getProvinceId($row['province'], $region_id);
                $municipality_id = $this->getMunicipalityId($row['municipality'], $province_id);
                $district_id = $this->getDistrictId($row['district'], $municipality_id);
                $particular_id = $this->getParticularId($row['particular'], $district_id);
    
                $legislator = Legislator::where('name', $row['legislator'])->first();
    
                if (!$legislator) {
                    $legislator = Legislator::create(['name' => $row['legislator']]);
                }
    
                $legislator->particular()->syncWithoutDetaching([$particular_id]);
    
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

    protected function getParticularId(string $particularName, int $districtId) {
        
        $particular = Particular::where('name', $particularName)
            ->where('district_id', $districtId)
            ->whereNull('deleted_at')
            ->first();
        
        if (!$particular) {
            throw new \Exception("Particular with name '{$particularName}' not found. No changes were saved.");
        }

        return $particular->id;

    }

    protected function getDistrictId(string $districtName, int $municipalityId) {

        $district = District::where('name', $districtName)
            ->where('municipality_id', $municipalityId)
            ->whereNull('deleted_at')
            ->first();
        
        if (!$district) {
            throw new \Exception("District with name '{$districtName}' not found. No changes were saved.");
        }

        return $district->id;
    }

    protected function getMunicipalityId(string $municipalityName, int $provinceId) {

        $municipality = Municipality::where('name', $municipalityName)
            ->where('province_id', $provinceId)
            ->whereNull('deleted_at')
            ->first();
        
        if (!$municipality) {
            throw new \Exception("Municipality with name '{$municipalityName}' not found. No changes were saved.");
        }

        return $municipality->id;
    }

    protected function getProvinceId(string $provinceName, int $regionId) {

        $province = Province::where('name', $provinceName)
            ->where('region_id', $regionId)
            ->whereNull('deleted_at')
            ->first();
        
        if (!$province) {
            throw new \Exception("Province with name '{$provinceName}' not found. No changes were saved.");
        }

        return $province->id;
    }

    protected function getRegionId(string $regionName) {

        $region = Region::where('name', $regionName)
            ->whereNull('deleted_at')
            ->first();
        
        if (!$region) {
            throw new \Exception("Region with name '{$regionName}' not found. No changes were saved.");
        }

        return $region->id;
    }
}
