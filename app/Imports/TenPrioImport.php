<?php

namespace App\Imports;

use Throwable;
use App\Models\Priority;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TenPrioImport implements ToModel, WithHeadingRow
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

                $sectorIsExist = Priority::where('name', $row['sector_name'])->exists();

                if (!$sectorIsExist) {
                    return new Priority([
                        'name' => $row['sector_name'],
                    ]);
                }

            } catch (Throwable $e) {

                Log::error('Failed to import TEN Priority Sectors: ' . $e->getMessage());
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
