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

                $isNCR = Region::find($region_id)->name === 'NCR';
                $municipality_id = null;

                if ($isNCR) {
                    $municipality_id = $this->getMunicipalityId($province_id, $row['municipality']);
                }

                // Check if the district already exists
                $districtIsExist = District::where('name', $row['district'])
                    ->where('code', $row['code'])
                    ->where('municipality_id', $municipality_id)
                    ->exists();

                if (!$districtIsExist) {
                    // Create new District
                    $district = District::create([
                        'code' => $row['code'],
                        'name' => $row['district'],
                        'municipality_id' => $municipality_id,
                        'province_id' => $province_id,
                        'region_id' => $region_id,
                    ]);

                    // Now attach the municipality to the district if it's NCR (many-to-many relationship)
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
        Log::info("Searching for region: Name = {$regionName}");

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

    // public function getMunicipalityId(int $provinceId, string $municipalityName = null)
    // {
    //     $province = Province::find($provinceId);
    //     if ($province && $province->region->name !== 'NCR') {
    //         return null;
    //     }

    //     if (!$municipalityName) {
    //         throw new \Exception("Municipality name is required for NCR province.");
    //     }

    //     $municipality = Municipality::where('name', $municipalityName)
    //         ->where('province_id', $provinceId)
    //         ->whereNull('deleted_at')
    //         ->first();

    //     if (!$municipality) {
    //         throw new \Exception("Municipality with name '{$municipalityName}' in province ID '{$provinceId}' not found. No changes were saved.");
    //     }

    //     return $municipality->id;
    // }
    public function getMunicipalityId(int $provinceId, string $municipalityName)
    {
        $municipality = Municipality::where('name', $municipalityName)
            ->where('province_id', $provinceId)
            ->whereNull('deleted_at')
            ->first();

        if (!$municipality) {
            Log::info("Creating new municipality: '{$municipalityName}' in province ID '{$provinceId}'");

            $municipality = Municipality::create([
                'name' => $municipalityName,
                'province_id' => $provinceId,
            ]);
        }

        return $municipality->id;
    }




}
