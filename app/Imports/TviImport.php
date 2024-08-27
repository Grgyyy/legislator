<?php
namespace App\Imports;

use App\Models\Tvi;
use App\Models\District;
use App\Models\TviClass;
use App\Models\InstitutionClass;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TviImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $tvi_class = self::getTviClassId($row['institution_class_a']);
        $institution_class = self::getInstitutionClass($row['institution_class_b']);
        $district_id = self::getDistrictId($row['district']);

        return new Tvi([
            'name' => $row['name'],
            'tvi_class_id' => $tvi_class,
            'institution_class_id' => $institution_class,
            'district_id' => $district_id,
            'address' => $row['address'],
        ]);
    }

    public static function getDistrictId(string $districtName)
    {
        $district = District::where('name', $districtName)->first();

        if (!$district) {
            throw new \Exception("District '{$districtName}' not found.");
        }

        return $district->id;
    }

    public static function getTviClassId(string $tviClass)
    {
        $tviClass = TviClass::where('name', $tviClass)->first();

        if (!$tviClass) {
            throw new \Exception("Institution Class A '{$tviClass}' not found.");
        }

        return $tviClass->id;
    }

    public static function getInstitutionClass(string $institutionClassA)
    {
        $institutionClass = InstitutionClass::where('name', $institutionClassA)->first();

        if (!$institutionClass) {
            throw new \Exception("Institution Class B '{$institutionClassA}' not found.");
        }

        return $institutionClass->id;
    }
}
