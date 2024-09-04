<?php

namespace App\Imports;

use App\Models\District;
use App\Models\Municipality;
use App\Models\Region;
use App\Models\Province;
use App\Models\Particular;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Throwable;

class ParticularImport implements ToModel, WithHeadingRow
{
    use Importable;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $region_id = $this->getRegionId($row['region']);
                $province_id = $this->getProvinceId($region_id, $row['province']);
                $municipality_id = $this->getMunicipalityId($region_id, $province_id, $row['municipality']);
                $district_id = $this->getDistrictId($municipality_id, $row['district']);

                $particularIsExist = Particular::where('name', $row['particular'])
                    ->where('district_id', $district_id)
                    ->exists();

                if(!$particularIsExist) {
                    return new Particular([
                        'name' => $row['particular'],
                        'district_id' => $district_id
                    ]);
                }
                
            } catch (Throwable $e) {
                Log::error('Failed to import particular: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['particular', 'district', 'municipality', 'province', 'region'];
        $particularOptions = ['District', 'Party List', 'Senator', 'Vetted', 'Regular', 'Star Rated', 'APACC', 'EO79', 'EO70', 'KIA/WIA'];

        if (!in_array($row['particular'], $particularOptions)) {
            throw new \Exception("It seems the particular name you’ve entered is not included in the available options. Please check the list and select a valid particular name.");
        }

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("Validation error: The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    public function getRegionId(string $regionName)
    {
        $region = Region::where('name', $regionName)
            ->whereNull('deleted_at')
            ->first();

        if (!$region) {
            throw new \Exception("Region with name '{$regionName}' not found. No changes were saved.");
        }

        return $region->id;
    }

    public function getProvinceId(int $regionId, string $provinceName)
    {
        $province = Province::where('name', $provinceName)
            ->where('region_id', $regionId)
            ->whereNull('deleted_at')
            ->first();

        if (!$province) {
            throw new \Exception("Province with name '{$provinceName}' in region ID '{$regionId}' not found. No changes were saved.");
        }

        return $province->id;
    }

    public function getMunicipalityId(int $regionId, int $provinceId, string $municipalityName)
    {
        $municipality = Municipality::where('name', $municipalityName)
            ->where('province_id', $provinceId)
            ->whereNull('deleted_at')
            ->first();

        if (!$municipality) {
            throw new \Exception("Municipality with name '{$municipalityName}' in province ID '{$provinceId}' not found. No changes were saved.");
        }

        return $municipality->id;
    }

    public function getDistrictId(int $municipalityId, string $districtName)
    {
        $district = District::where('name', $districtName)
            ->where('municipality_id', $municipalityId)
            ->whereNull('deleted_at')
            ->first();

        if (!$district) {
            throw new \Exception("District with name '{$districtName}' in municipality ID '{$municipalityId}' not found. No changes were saved.");
        }

        return $district->id;
    }
}
