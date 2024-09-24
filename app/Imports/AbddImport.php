<?php

namespace App\Imports;

use App\Models\Province;
use App\Models\Abdd;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class AbddImport implements ToModel, WithHeadingRow
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
                // Retrieve or create the sector record
                $sectorRecord = Abdd::firstOrCreate(['name' => $row['sector_name']]);

                // Get province ID
                $provinceId = $this->getProvinceId($row['province']);
                
                if ($provinceId) {
                    $sectorRecord->provinces()->syncWithoutDetaching([$provinceId]);
                } else {
                    Log::warning("Province '{$row['province']}' not found. No provinces linked to sector '{$row['sector_name']}'.");
                }

            } catch (Throwable $e) {
                Log::error('Failed to import Abdd Sectors: ' . $e->getMessage(), ['row' => $row]);
                throw $e;
            }
        });
    }

    /**
     * Validate the row to ensure all required fields are present and not empty.
     *
     * @param array $row
     * @throws \Exception
     */
    protected function validateRow(array $row)
    {
        $requiredFields = ['sector_name', 'province'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function getProvinceId($provinceName) {
        $provinceRecord = Province::where('name', $provinceName)->first();
        
        return $provinceRecord ? $provinceRecord->id : throw new \Exception("The {$provinceName} Province does not exists."); // Return null if not found
    }
}
