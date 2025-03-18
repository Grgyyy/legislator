<?php

namespace App\Imports;


use App\Models\Toolkit;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class NoOfToolkitsImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        try {
            $this->validateRow($row);

            DB::transaction(function () use ($row) {
                $toolkit = Toolkit::where('lot_name', $row['lot_name'])
                    ->where('year', $row['year'])
                    ->first();

                if (!$toolkit) {
                    throw new \Exception("Toolkit named {$row['lot_name']} doesn't exist.");
                }

                if ($toolkit->number_of_toolkits !== null) {
                    throw new \Exception("Toolkit named {$row['lot_name']} is already has a number of toolkits.");
                }

                $toolkit->update([
                    'available_number_of_toolkits' => $row['no_of_toolkits'],
                    'number_of_toolkits' => $row['no_of_toolkits'],
                    'total_abc_per_lot' => $toolkit->price_per_toolkit * $row['no_of_toolkits'],
                ]);

                return $toolkit;

            });
        } catch (Throwable $e) {
            throw $e;
        }
    }


    protected function validateRow(array $row)
    {
        $requiredFields = [
            'lot_name',
            'year',
            'no_of_toolkits',
        ];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function validateYear(int $year)
    {
        $currentYear = date('Y');
        if ($year < $currentYear) {
            throw new \Exception("The provided year '{$year}' must be the current year or a future year.");
        }
    }
}
