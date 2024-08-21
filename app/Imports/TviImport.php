<?php

namespace App\Imports;

use App\Models\Tvi;
use App\Models\Region;
use App\Models\District;
use App\Models\Province;
use App\Models\TviClass;
use App\Models\Municipality;
use App\Models\InstitutionClass;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TviImport implements ToModel, WithHeadingRow
{
    use Importable;
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model/null
     */
    public function model(array $row)
    {
        $institution_class = self::getInstitutionClassAId($row['institution_class_a']);
        $tvi_class = self::getInstitutionClassBId($row['institution_class_b']);
        $district_id = self::getDistrictId($row['district']);
        return new Tvi([
            'name' => $row['name'],
            'institution_class_id' => $institution_class,
            'tvi_class_id' => $tvi_class,
            'district_id' => $district_id,
            'address' => $row['address'],
        ]);
    }



    public static function getDistrictId(string $districtName)
    {
        $district = District::where('name', $districtName)
            ->first();
        return $district ? $district->id : null;
    }
    // public static function getInstitutionClassAId(string $instituionClassA)
    // {
    //     return InstitutionClass::where('name', $instituionClassA)
    //         ->first()
    //         ->id;
    // }

    public static function getInstitutionClassAId(string $institutionClassA)
    {
        return InstitutionClass::where('name', $institutionClassA)
            ->first()
            ->id;


    }

    public static function getInstitutionClassBId(string $instituionClassB)
    {
        return TviClass::where('name', $instituionClassB)
            ->first()
            ->id;
    }
}
