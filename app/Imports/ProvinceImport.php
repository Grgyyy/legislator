<?php

namespace App\Imports;

use App\Models\Region;
use App\Models\Province;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProvinceImport implements ToModel, WithHeadingRow
{
    use Importable;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model/null
     */

    public function model(array $row)
    {

        $region_id = self::getRegionId($row['region']);

        return new Province([
            'name' => $row['province'],
            'region_id' => $region_id,
        ]);
    }

    public function getRegionId(string $regionName)
    {
        $region = Region::where('name', $regionName)->first();

        if ($region) {
            return $region->id;
        }

        return null;
    }
}
