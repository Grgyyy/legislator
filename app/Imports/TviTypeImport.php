<?php

namespace App\Imports;

use Throwable;
use App\Models\TviType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TviTypeImport implements ToModel, WithHeadingRow
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

                $typeIsExist = TviType::where('name', $row['tvi_type'])->exists();

                if (!$typeIsExist) {

                    return new TviType([
                        'name' => $row['tvi_type'],
                    ]);

                }
            } catch (Throwable $e) {

                Log::error('Failed to import TVI Type: ' . $e->getMessage());
                throw $e;

            }
        });
    }

    /**
     *
     *
     * @param array $row
     * @throws \Exception
     */
    protected function validateRow(array $row)
    {
        if (empty($row['tvi_type'])) {
            throw new \Exception("The TVI Type is required and cannot be null or empty. No changes were saved.");
        }
    }
}
