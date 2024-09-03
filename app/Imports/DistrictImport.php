<?php

namespace App\Imports;

use App\Models\Region;
use App\Models\District;
use App\Models\Province;
use App\Models\Municipality;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Throwable;

class DistrictImport implements ToModel, WithHeadingRow
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
                $municipality_id = $this->getMunicipalityId($province_id, $row['municipality']);

                $districtIsExist = District::where('name', $row['district'])
                    ->where('municipality_id', $municipality_id)
                    ->exists();

                if(!$districtIsExist) {
                    return new District([
                        'name' => $row['district'],
                        'municipality_id' => $municipality_id,
                    ]);
                }

            } catch (Throwable $e) {
                Log::error('Failed to import district: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['district', 'municipality', 'province', 'region'];

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

    public function getMunicipalityId(int $provinceId, string $municipalityName)
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
}
