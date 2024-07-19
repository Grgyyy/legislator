<?php

namespace App\Imports;

use App\Models\Legislator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LegislatorImport implements ToModel, WithHeadingRow
{
    use Importable;
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Legislator([
            'legislator_name'  => $row['legislator_name'],
            'particular' => $row['particular']
        ]);
    }
}
