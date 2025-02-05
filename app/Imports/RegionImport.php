<?php

namespace App\Imports;

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
                $regionIsExist = Region::where('code', $row['code'])
                    ->where('name', $row['region'])
                    ->exists();

                if (!$regionIsExist) {
                    return new Region([
                        'code' => $row['code'],
                        'name' => $row['region'],
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
        if (empty($row['code']) && ($row['region'])) {
            throw new \Exception("Validation error: The field 'region' is required and cannot be null or empty. No changes were saved.");
        }
    }
}
