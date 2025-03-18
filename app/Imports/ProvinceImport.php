<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class ProvinceImport implements ToModel, WithHeadingRow
{
    use Importable;
    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $regionName = Helper::capitalizeWords($row['region']);
                $provinceName = Helper::capitalizeWords($row['province']);

                $region_id = $this->getRegionId($regionName);

                $provinceIsExist = Province::where('name', $provinceName)
                    ->where('region_id', $region_id)
                    ->where('code', $row['code'])
                    ->exists();

                if (!$provinceIsExist) {
                    return new Province([
                        'code' => $row['code'],
                        'name' => $provinceName,
                        'region_id' => $region_id,
                    ]);
                }
            } catch (Throwable $e) {
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['code', 'province', 'region'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
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
}
