<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\Abdd;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

                Log::info("Abdd with name '{$sectorName}' already exists. No new record created.");
            } catch (Throwable $e) {
                Log::error("Failed to import Abdd: " . $e->getMessage(), ['row' => $row]);

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
