<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\Tvet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class TvetImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $sectorName = Helper::capitalizeWords($row['sector_name']);

                $sectorIsExist = Tvet::where('name', $sectorName)->exists();

                if (!$sectorIsExist) {
                    return new Tvet([
                        'name' => $sectorName,
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('Failed to import TVET Sectors: ' . $e->getMessage());

                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        if (empty($row['sector_name'])) {
            throw new \Exception("The Sector Name field is required and cannot be null or empty. No changes were saved.");
        }
    }
}
