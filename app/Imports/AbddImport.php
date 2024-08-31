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
        // Validate the row to ensure no required fields are empty
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {

                return new Abdd([
                    'name' => $row['sector'],
                ]);
            } catch (Throwable $e) {

                Log::error('Failed to import Abdd: ' . $e->getMessage());
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
        // Check if the 'sector' field is present and not empty
        if (empty($row['sector'])) {
            throw new \Exception("Validation error: The 'sector' field is required and cannot be null or empty. No changes were saved.");
        }
    }
}
