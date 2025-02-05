<?php

namespace App\Imports;

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
                $sectorIsExist = Tvet::where('name', $row['sector_name'])->exists();

                if (!$sectorIsExist) {
                    return new Tvet([
                        'name' => $row['sector_name'],
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