<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\InstitutionClass;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class InstitutionClassImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $institutionClassName = Helper::capitalizeWords($row['institution_class']);

                $classIsExist = InstitutionClass::where('name', $institutionClassName)
                    ->exists();

                if (!$classIsExist) {
                    return new InstitutionClass([
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
        if (empty($row['institution_class'])) {
            throw new \Exception("The Institution Class is required and cannot be null or empty. No changes were saved.");
        }
    }
}
