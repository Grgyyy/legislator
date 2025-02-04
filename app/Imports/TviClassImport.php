<?php

namespace App\Imports;

use Throwable;
use App\Models\TviClass;
use App\Models\TviType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class TviClassImport implements ToModel, WithHeadingRow
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
                $classIsExist = TviClass::where('name', $row['institution_class'])
                    ->exists();

                if (!$classIsExist) {
                    return new TviClass([
                        'name' => $row['institution_class'],
                    ]);
                }

            } catch (Throwable $e) {
                Log::error('Failed to import Institution Class(A): ' . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     *
     * @param array $row
     * @throws \Exception
     */
    protected function validateRow(array $row)
    {
        $requiredFields = ['institution_class'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }
}
