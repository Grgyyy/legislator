<?php

namespace App\Imports;

use App\Models\InstitutionClass;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InstitutionClassImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     */

    use Importable;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if (isset($row['name'])) {
                InstitutionClass::create([
                    'name' => $row['name'],
                ]);
            } else {
                Log::warning('Skipped row as it does not contain a "name" column', ['row' => $row]);
            }
        }
    }
}
