<?php

namespace App\Imports;

use Throwable;
use App\Models\Abdd;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

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

                $sectorIsExist = Abdd::where('name', $row['sector_name'])->exists();

                if (!$sectorIsExist) {
                    return new Abdd([
                        'name' => $row['sector_name'],
                    ]);
                }

            } catch (Throwable $e) {

                Log::error('Failed to import Abdd Sectors: ' . $e->getMessage());
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
        if (empty($row['sector_name'])) {
            throw new \Exception("The Sector Name field is required and cannot be null or empty. No changes were saved.");
        }
    }
}
