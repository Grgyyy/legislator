<?php

namespace App\Imports;

use App\Models\Tvet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
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

                return new Tvet([
                    'name' => $row['sector'],
                ]);
            } catch (Throwable $e) {

                Log::error('Failed to import TVET Sector: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        if (empty($row['sector'])) {
            throw new \Exception("Validation error: The 'sector' field is required and cannot be null or empty. No changes were saved.");
        }
    }
}
