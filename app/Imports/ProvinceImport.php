<?php

namespace App\Imports;

use App\Models\Region;
use App\Models\Province;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Throwable;

class ProvinceImport implements ToModel, WithHeadingRow
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
                $provinceIsExist = Province::where('name', $row['province'])
                    ->where('region_id', $region_id)
                    ->exists();

                if(!$provinceIsExist) {
                    return new Province([
                        'name' => $row['province'],
                        'region_id' => $region_id,
                    ]);
                }

            } catch (Throwable $e) {
                Log::error('Failed to import province: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['province', 'region'];

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
