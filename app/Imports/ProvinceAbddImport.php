<?php

namespace App\Imports;

use App\Models\Abdd;
use App\Models\Region;
use App\Models\Province;
use App\Models\ProvinceAbdd;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Throwable;

class ProvinceAbddImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $region_id = $this->getRegionId($row['region']);
                $province_id = $this->getProvinceId($region_id, $row['province']);
                $abdd_id = $this->getAbddId($row['abdd']);

                $provinceAbdd = ProvinceAbdd::where('province_id', $province_id)
                    ->where('abdd_id', $abdd_id)
                    ->where('available_slots', $row['available_slots'])
                    ->where('total_slots', $row['total_slots'])
                    ->where('year', $row['year'])
                    ->exists();

                if (!$provinceAbdd) {
                    return ProvinceAbdd::create([
                        'province_id' => $province_id,
                        'abdd_id' => $abdd_id,
                        'available_slots' => $row['available_slots'],
                        'total_slots' => $row['total_slots'],
                        'year' => $row['year'],
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
        $requiredFields = ['province_id', 'abdd_id', 'available_slots', 'total_slots', 'year'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function getRegionId($regionName)
    {
        $regionRecord = Region::where('name', $regionName)
            ->whereNull('deleted_at')
            ->first();

        if (!$regionRecord) {
            throw new \Exception("The {$regionName} region does not exist.");
        }

        return $regionRecord->id;
    }

    protected function getProvinceId($regionId, $provinceName)
    {
        $provinceRecord = Province::where('name', $provinceName)
            ->where('region_id', $regionId)
            ->whereNull('deleted_at')
            ->first();

        if (!$provinceRecord) {
            throw new \Exception("The {$provinceName} province does not exist.");
        }

        return $provinceRecord->id;
    }

    protected function getAbddId(string $abddName)
    {
        $abdd = Abdd::where('name', $abddName)
            ->first();

        if (!$abdd) {
            throw new \Exception("The {$abddName} ABDD does not exist.");
        }

        return $abdd->id;
    }
}
