<?php

namespace App\Imports;

use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use App\Models\District;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Throwable;

class MunicipalityImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $region_id = $this->getRegionId($row['region']);
                $province_id = $this->getProvinceId($region_id, $row['province']);

                $this->validateDistrictColumn($row['region'], $row['province'], $row['district'] ?? null);

                $municipality = Municipality::updateOrCreate(
                    [
                        'name' => $row['municipality'],
                        'code' => $row['code'],
                        'class' => $row['class'],
                        'province_id' => $province_id,
                    ],
                );

                if (isset($row['district'])) {
                    $district = District::firstOrCreate(
                        [
                            'name' => $row['district'],
                            'province_id' => $province_id,
                        ],
                        [
                            'municipality_id' => $municipality->id,
                        ]
                    );

                    $municipality->district()->syncWithoutDetaching($district->id);
                }

                return $municipality;

            } catch (Throwable $e) {
                Log::error('Failed to import municipality: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['municipality', 'region', 'province', 'code', 'class'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function validateDistrictColumn(string $regionName, string $provinceName, ?string $districtName)
    {
        $isNcrRegion = strtolower(trim($regionName)) === 'ncr';
        $isProvinceUnderNcr = $this->isProvinceUnderNcr($provinceName);

        if ($isNcrRegion && $isProvinceUnderNcr && empty($districtName)) {
            throw new \Exception("The District column is required for municipalities in NCR.");
        }

        if ((!$isNcrRegion || !$isProvinceUnderNcr) && !empty($districtName)) {
            throw new \Exception("The District column must be empty for municipalities outside NCR.");
        }
    }

    protected function isProvinceUnderNcr(string $provinceName): bool
    {
        $province = Province::where('name', $provinceName)
            ->whereHas('region', function ($query) {
                $query->where('name', 'NCR');
            })
            ->first();

        return $province !== null;
    }

    protected function getRegionId(string $regionName)
    {
        $region = Region::where('name', $regionName)
            ->whereNull('deleted_at')
            ->first();

        if (!$region) {
            throw new \Exception("Region with name '{$regionName}' not found. No changes were saved.");
        }

        return $region->id;
    }

    protected function getProvinceId(int $regionId, string $provinceName)
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
}
