<?php

namespace App\Imports;

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

    /**
     * Process each row of the import file and create or update the Abdd record.
     *
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     * @throws \Exception
     */
    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $abddExists = Abdd::where('name', $row['sector_name'])->exists();

                if (!$abddExists) {
                    return new Abdd([
                        'name' => $row['sector_name'],
                    ]);
                }

                Log::info("Abdd with name '{$row['sector_name']}' already exists. No new record created.");
            } catch (Throwable $e) {
                Log::error("Failed to import Abdd: " . $e->getMessage(), ['row' => $row]);
                
                throw $e;
            }
        });
    }

    /**
     * Validate the row to ensure required fields are present.
     *
     * @param array $row
     * @throws \Exception
     */
    protected function validateRow(array $row)
    {
        if (empty($row['sector_name'])) {
            throw new \Exception("The 'name' field is required and cannot be null or empty. No changes were saved.");
        }
    }
}