<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\Abdd;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class AbddImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $sectorName = Helper::capitalizeWords($row['sector_name']);

                $abddExists = Abdd::where('name', $sectorName)->exists();

                if (!$abddExists) {
                    return new Abdd([
                        'name' => $sectorName,
                    ]);
                }
            } catch (Throwable $e) {
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        if (empty($row['sector_name'])) {
            throw new \Exception("The 'name' field is required and cannot be null or empty. No changes were saved.");
        }
    }
}
