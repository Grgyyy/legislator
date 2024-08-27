<?php

namespace App\Imports;

use App\Models\Legislator;
use App\Models\Particular;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LegislatorImport implements ToModel, WithHeadingRow
{

    use Importable;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model/null
     */


    public function model(array $row)
    {


        $particular_id = self::getParticularId($row['particular']);

        $legislator = Legislator::create(
            ['name' => $row['name']],
        );

        $legislator->particular()->syncWithoutDetaching([$particular_id]);

        return $legislator;
    }

    public static function getParticularId(string $particular)
    {
        return Particular::where('name', $particular)
            ->first()
            ->id;
    }

}
