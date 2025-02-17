<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class RegionImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $regionName = Helper::capitalizeWords($row['region']);

                $regionIsExist = Region::where('code', $row['code'])
                    ->where('name', $regionName)
                    ->exists();

                if (!$regionIsExist) {
                    return new Region([
                        'code' => $row['code'],
                        'name' => $regionName,
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('Failed to import region: ' . $e->getMessage());

                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        if (empty($row['code']) || empty($row['region'])) {
            throw new \Exception("Validation error: The field 'region' is required and cannot be null or empty. No changes were saved.");
        }
    }
}
