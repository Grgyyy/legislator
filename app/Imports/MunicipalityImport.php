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

class MunicipalityImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $regionName = Helper::capitalizeWords($row['region']);
                $provinceName = Helper::capitalizeWords($row['province']);
                $districtName = Helper::capitalizeWords($row['district']);
                $municipalityName = Helper::capitalizeWords($row['municipality']);
                $municipalityClassName = Helper::capitalizeWords($row['class']);

                $regionId = $this->getRegionId($regionName);
                $provinceId = $this->getProvinceId($provinceName, $regionId);
                $districtId = $this->getDistrictId($districtName, $provinceId);

                $municipalityRecord = Municipality::where('code', $row['code'])
                    ->where('name', $municipalityName)
                    ->where('class', $municipalityClassName)
                    ->where('province_id', $provinceId)
                    ->first();

                if (!$municipalityRecord) {
                    $municipalityRecord = Municipality::create([
                        'code' => $row['code'],
                        'name' => $municipalityName,
                        'class' => $municipalityClassName,
                        'province_id' => $provinceId
                    ]);

                    $municipalityRecord->district()->attach($districtId);
                }

                return $municipalityRecord;
            } catch (Throwable $e) {
                Log::error('Failed to import municipality: ' . $e->getMessage());

                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['municipality', 'region', 'province', 'class', 'code'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function getRegionId(string $region)
    {
        $regionRecord = Region::where("name", $region)->first();

        if (!$regionRecord) {
            throw new \Exception("Region with the name '{$region}' does not exist.");
        }

        return $regionRecord->id;
    }

    protected function getProvinceId(string $province, int $regionId)
    {
        $provinceRecord = Province::where("name", $province)
            ->where("region_id", $regionId)
            ->first();

        if (!$provinceRecord) {
            throw new \Exception("Province with the name '{$province}' does not exist.");
        }

        return $provinceRecord->id;
    }

    protected function getDistrictId(string $district, int $provinceId)
    {
        $districtRecord = District::where("name", $district)
            ->whereNull("municipality_id")
            ->where("province_id", $provinceId)
            ->first();

        $province = Province::find($provinceId);

        if (!$districtRecord) {
            throw new \Exception("District with the name '{$district}' under the province of '{$province->name}' does not exist.");
        }

        return $districtRecord->id;
    }
}
