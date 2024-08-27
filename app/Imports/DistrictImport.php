<?php

namespace App\Imports;

use App\Models\Region;
use App\Models\District;
use App\Models\Province;
use App\Models\Municipality;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DistrictImport implements ToModel, WithHeadingRow
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
        $municipality_id = self::getMunicipalityId($region_id, $province_id, $row['municipality']);

        return new District([
            'name' => $row['name'],
            'municipality_id' => $municipality_id,
            'province_id' => $province_id,
            'region_id' => $region_id,
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
    public static function getMunicipalityId(int $regionId, int $provinceId, string $municipalityName)
    {
        return Municipality::where('name', $municipalityName)
            ->where('province_id', $provinceId)
            ->first()
            ->id;
    }

}
