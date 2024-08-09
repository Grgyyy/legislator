<?php

namespace App\Imports;

use App\Models\QualificationTitle;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QualificationTitleImport implements ToModel, WithHeadingRow
{
    use Importable;
    /** 
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new QualificationTitle([
            'code' => $row['code'],
            'title' => $row['title'],
            'scholarship_program_id' => $row['scholarship_program_id'],
            'sector_id' => $row['sector_id'],
            'duration' => $row['duration'],
            'training_cost_pcc' => $row['training_cost_pcc'],
            'cost_of_toolkit_pcc' => $row['cost_of_toolkit_pcc'],
            'status_id' => $row['status_id'],
        ]);
    }
}
