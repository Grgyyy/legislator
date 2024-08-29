<?php

namespace App\Imports;

use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MunicipalityImport implements ToModel, WithHeadingRow
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
        $province_id = self::getProvinceId($region_id, $row['province']);

        return new Municipality([
            'name' => $row['municipality'],
            'region_id' => $region_id,
            'province_id' => $province_id,
        ]);
    }


    public static function getRegionId(string $region)
    {
        return Region::where('name', $region)
            ->first()
            ->id;
    }
    public static function getProvinceId(int $regionId, string $provinceName)
    {
        return Province::where('name', $provinceName)
            ->where('region_id', $regionId)
            ->first()
            ->id;
    }



}
