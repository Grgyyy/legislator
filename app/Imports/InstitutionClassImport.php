<?php

namespace App\Imports;

use Throwable;
use App\Models\InstitutionClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InstitutionClassImport implements ToModel, WithHeadingRow
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
                $classIsExist = InstitutionClass::where('name', $row['institution_class'])
                    ->exists();

                if (!$classIsExist) {
                    return new InstitutionClass([
                        'name' => $row['institution_class'],
                    ]);
                }
            }
            catch (Throwable $e) {

                Log::error('Failed to import Institution Class(B): ' . $e->getMessage());
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
        if (empty($row['institution_class'])) {
            throw new \Exception("The Institution Class is required and cannot be null or empty. No changes were saved.");
        }
    }
}
