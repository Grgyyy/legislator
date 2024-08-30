<?php

namespace App\Imports;

use App\Models\ScholarshipProgram;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ScholarshipProgramImport implements ToModel, WithHeadingRow
{
    use Importable;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model/null
     */
    public function model(array $row)
    {
        return new ScholarshipProgram([
            'code' => $row['code'],
            'name' => $row['scholarship_program'],
            'desc' => $row['description'],
        ]);
    }

}
