<?php

namespace App\Imports;

use App\Models\Region;
use App\Models\Tvi;
use App\Models\District;
use App\Models\TviClass;
use App\Models\InstitutionClass;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\TviType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class TviImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {

        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {

                $tviClassId = $this->getInstitutionClassA($row['institution_class_a']);
                $institutionClassId = $this->getInstitutionClassB($row['institution_class_b']);
                $regionId = $this->getRegionId($row['region']);
                $provinceId = $this->getProvinceId($regionId, $row['province']);
                $municipalityId = $this->getMunicipalityId($provinceId, $row['municipality']);
                $districtId = $this->getDistrictId($municipalityId, $row['district']);


                $tviRecord = Tvi::where('name', $row['institution_name'])
                    ->where('district_id', $districtId)
                    ->first();

                if (!$tviRecord) {
                    $tviRecord = Tvi::create([
                        'school_id' => $row['school_id'],
                        'name' => $row['institution_name'],
                        'institution_class_id' => $institutionClassId,
                        'tvi_class_id' => $tviClassId,
                        'district_id' => $districtId,
                        'address' => $row['full_address'],
                    ]);
                }
                else {
                    $tviRecord->update([
                        'institution_class_id' => $institutionClassId,
                        'tvi_class_id' => $tviClassId,
                        'address' => $row['full_address'],
                    ]);

                    return $tviRecord;

                }
                
                
            } catch (Throwable $e) {
                DB::rollBack(); // Rollback the transaction in case of error
                Log::error("An error occurred while importing row: " . json_encode($row) . " Error: " . $e->getMessage());
                throw $e; // Re-throw the exception to handle it outside of the transaction
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['institution_name', 'institution_class_a', 'institution_class_b', 'district', 'municipality', 'province', 'region', 'full_address'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function getInstitutionClassA(string $institutionClassA) {
        $class = TviClass::where('name', $institutionClassA)
            ->whereNull('deleted_at')
            ->first();

        if (!$class) {
            throw new \Exception("TVI Type with name '{$institutionClassA}' not found. No changes were saved.");
        }

        return $class->id;
    }

    protected function getInstitutionClassB(string $institutionClassB) {
        $class = InstitutionClass::where('name', $institutionClassB)
            ->whereNull('deleted_at')
            ->first();

        if (!$class) {
            throw new \Exception("TVI Type with name '{$institutionClassB}' not found. No changes were saved.");
        }

        return $class->id;
    }

    protected function getRegionId(string $regionName)
    {
        $region = Region::where('name', $regionName)
            ->whereNull('deleted_at')
            ->first();

        if (!$region) {
            throw new \Exception("Region with name '{$regionName}' not found. No changes were saved.");
        }

        return $region->id;
    }

    protected function getProvinceId(int $regionId, string $provinceName)
    {
        $province = Province::where('name', $provinceName)
            ->where('region_id', $regionId)
            ->whereNull('deleted_at')
            ->first();

        if (!$province) {
            throw new \Exception("Province with name '{$provinceName}' in region ID '{$regionId}' not found. No changes were saved.");
        }

        return $province->id;
    }

    protected function getMunicipalityId(int $provinceId, string $municipalityName)
    {
        $municipality = Municipality::where('name', $municipalityName)
            ->where('province_id', $provinceId)
            ->whereNull('deleted_at')
            ->first();

        if (!$municipality) {
            throw new \Exception("Municipality with name '{$municipalityName}' in province ID '{$provinceId}' not found. No changes were saved.");
        }

        return $municipality->id;
    }

    protected function getDistrictId(int $municipalityId, string $districtName)
    {
        $fullDistrictName = $districtName . ' ' . 'District'; // Concatenate the district name with 'District'

        $district = District::where('name', $fullDistrictName)
            ->where('municipality_id', $municipalityId)
            ->whereNull('deleted_at')
            ->first();

        if (!$district) {
            throw new \Exception("District with name '{$fullDistrictName}' in municipality ID '{$municipalityId}' not found. No changes were saved.");
        }

        return $district->id;
    }

}
