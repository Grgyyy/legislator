<?php

namespace App\Imports;

use App\Models\District;
use App\Models\Municipality;
use App\Models\Region;
use App\Models\Province;
use App\Models\Particular;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;



class ParticularImport implements ToModel, WithHeadingRow
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
        $district_id = self::getDistrictId($municipality_id, $row['district']);

        return new Particular([
            'name' => $row['name'],
            'district_id' => $district_id,
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
    public static function getDistrictId(int $municipalityId, string $districtName)
    {
        return District::where('name', $districtName)
            ->where('municipality_id', $municipalityId)
            ->first()
            ->id;
    }


}
