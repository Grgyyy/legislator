<?php

namespace App\Imports;

use App\Models\Tvi;
use App\Models\District;
use App\Models\TviClass;
use App\Models\InstitutionClass;
use App\Models\Municipality;
use App\Models\Province;
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
        // Check for empty values in required fields
        foreach (['name', 'institution_class_a', 'institution_class_b', 'district', 'province', 'municipality'] as $field) {
            if (empty($row[$field])) {
                throw new \Exception("Required field '{$field}' is empty.");
            }
        }

        return DB::transaction(function () use ($row) {
            try {
                // Retrieve IDs for related models
                $tviClassId = $this->getTviClassId($row['institution_class_a']);
                $institutionClassId = $this->getInstitutionClass($row['institution_class_b']);
                $districtId = $this->getDistrictId($row['district']);
                $municipalityId = $this->getMunicipalityId($row['municipality']);
                $provinceId = $this->getProvinceId($row['province']);

                // Validate the relationships
                $district = District::find($districtId);
                if (!$district || $district->municipality_id !== $municipalityId) {
                    throw new \Exception("District '{$row['district']}' does not belong to Municipality '{$row['municipality']}'.");
                }

                $municipality = Municipality::find($municipalityId);
                if (!$municipality || $municipality->province_id !== $provinceId) {
                    throw new \Exception("Municipality '{$row['municipality']}' does not belong to Province '{$row['province']}'.");
                }

                $province = Province::find($provinceId);
                if (!$province) {
                    throw new \Exception("Province '{$row['province']}' not found.");
                }

                // Create or update the Tvi model
                $tvi = new Tvi([
                    'name' => $row['name'],
                    'tvi_class_id' => $tviClassId,
                    'institution_class_id' => $institutionClassId,
                    'district_id' => $districtId,
                    'municipality_id' => $municipalityId,
                    'province_id' => $provinceId,
                    'address' => $row['address'],
                ]);

                $tvi->save();

                DB::commit();
                return $tvi;
            } catch (Throwable $e) {
                DB::rollBack(); // Rollback the transaction in case of error
                Log::error("An error occurred while importing row: " . json_encode($row) . " Error: " . $e->getMessage());
                throw $e; // Re-throw the exception to handle it outside of the transaction
            }
        });
    }

    public function getDistrictId(string $districtName)
    {
        $district = District::where('name', $districtName)->first();

        if (!$district) {
            throw new \Exception("District '{$districtName}' not found.");
        }

        return $district->id;
    }

    public function getTviClassId(string $tviClassName)
    {
        $tviClass = TviClass::where('name', $tviClassName)->first();

        if (!$tviClass) {
            throw new \Exception("Institution Class A '{$tviClassName}' not found.");
        }

        return $tviClass->id;
    }

    public function getInstitutionClass(string $institutionClassName)
    {
        $institutionClass = InstitutionClass::where('name', $institutionClassName)->first();

        if (!$institutionClass) {
            throw new \Exception("Institution Class B '{$institutionClassName}' not found.");
        }

        return $institutionClass->id;
    }

    public function getMunicipalityId(string $municipalityName)
    {
        $municipality = Municipality::where('name', $municipalityName)->first();

        if (!$municipality) {
            throw new \Exception("Municipality '{$municipalityName}' not found.");
        }

        return $municipality->id;
    }

    public function getProvinceId(string $provinceName)
    {
        $province = Province::where('name', $provinceName)->first();

        if (!$province) {
            throw new \Exception("Province '{$provinceName}' not found.");
        }

        return $province->id;
    }
}
