<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\District;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class DistrictImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $regionName = Helper::capitalizeWords($row['region']);
                $provinceName = Helper::capitalizeWords($row['province']);
                $municipalityName = Helper::capitalizeWords($row['municipality']);
                $districtName = Helper::capitalizeWords($row['district']);

                $region_id = $this->getRegionId($regionName);
                $province_id = $this->getProvinceId($region_id, $provinceName);
                $isNCR = Region::find($region_id)->name === 'NCR';
                $municipality_id = null;

                $districtIsExist = District::where('name', $districtName)
                    ->where('code', $row['code'])
                    ->where('municipality_id', $municipality_id)
                    ->where('province_id', $province_id)
                    ->exists();

                if (!$districtIsExist) {
                    $district = District::create([
                        'code' => $row['code'],
                        'name' => $districtName,
                        'municipality_id' => $municipality_id,
                        'province_id' => $province_id,
                        'region_id' => $region_id,
                    ]);

                    if ($municipality_id) {
                        $municipality = Municipality::find($municipality_id);
                        $district->municipality()->attach($municipality);
                    }

                    return $district;
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
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    public function getRegionId(string $regionName)
    {
        $region = Region::where('name', $regionName)
            ->whereNull('deleted_at')
            ->first();

        if (!$region) {
            Log::error("Region not found: Name = '{$regionName}'");

            throw new \Exception("Region not found: Name = '{$regionName}'. No changes were saved.");
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
            $municipality = Municipality::create([
                'name' => $municipalityName,
                'province_id' => $provinceId,
            ]);
        }

        return $municipality->id;
    }
}
