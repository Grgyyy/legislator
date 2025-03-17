<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\TviClass;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class TviClassImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $institutionClassName = Helper::capitalizeWords($row['institution_class']);

                $classIsExist = TviClass::where('name', $institutionClassName)->exists();

                if (!$classIsExist) {
                    return new TviClass([
                        'name' => $institutionClassName,
                    ]);
                }

            } catch (Throwable $e) {
                throw $e;
            }
        });
    }

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
