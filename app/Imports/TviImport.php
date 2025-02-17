<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\District;
use App\Models\InstitutionClass;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use App\Models\Tvi;
use App\Models\TviClass;
use App\Models\TviType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
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
                $tviTypeId = $this->getInstitutionType($row['institution_type']);
                $tviClassId = !empty($row['institution_class_a']) && is_string($row['institution_class_a'])
                    ? $this->getInstitutionClassA($row['institution_class_a'])
                    : null;

                $institutionClassId = !empty($row['institution_class_b']) && is_string($row['institution_class_b'])
                    ? $this->getInstitutionClassB($row['institution_class_b'])
                    : null;

                $regionId = $this->getRegionId($row['region']);
                $provinceId = $this->getProvinceId($regionId, $row['province']);
                $municipalityId = $this->getMunicipalityId($provinceId, $row['municipality']);
                $districtId = $this->getDistrictId($regionId, $provinceId, $municipalityId, $row['district']);
                $tviCode = $row['school_id'] ? $row['school_id'] : null;

                $institutionName = Helper::capitalizeWords($row['institution_name']);

                $tviRecord = Tvi::where(DB::raw('LOWER(name)'), strtolower($institutionName))
                    ->where('address', $row['full_address'])
                    ->first();

                if (!$tviRecord) {
                    $tviRecord = Tvi::create([
                        'school_id' => $tviCode,
                        'name' => $institutionName,
                        'tvi_type_id' => $tviTypeId,
                        'institution_class_id' => $institutionClassId,
                        'tvi_class_id' => $tviClassId,
                        'district_id' => $districtId,
                        'municipality_id' => $municipalityId,
                        'address' => $row['full_address'],
                    ]);
                }

                return $tviRecord;

            } catch (Throwable $e) {
                DB::rollBack();
                Log::error("An error occurred while importing row: " . json_encode($row) . " Error: " . $e->getMessage());
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['institution_name', 'institution_type', 'institution_class_a', 'district', 'municipality', 'province', 'region', 'full_address'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function getInstitutionType(string $institutionType)
    {
        $type = TviType::where("name", $institutionType)
            ->whereNull("deleted_at")
            ->first();

        if (!$type) {
            throw new \Exception("Institution Type with name '{$institutionType}' not found. No changes were saved.");
        }
        return $type->id;
    }

    protected function getInstitutionClassA(string $institutionClassA)
    {
        $class = TviClass::where('name', $institutionClassA)
            ->whereNull('deleted_at')
            ->first();

        if (!$class) {
            throw new \Exception("Institution Class (A) with name '{$institutionClassA}' not found. No changes were saved.");
        }

        return $class->id;
    }

    protected function getInstitutionClassB(string $institutionClassB)
    {
        $class = InstitutionClass::where('name', $institutionClassB)
            ->whereNull('deleted_at')
            ->first();

        if (!$class) {
            throw new \Exception("Institution Class (B) with name '{$institutionClassB}' not found. No changes were saved.");
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
            Log::warning("Municipality not found. Using default ID.", [
                'municipality_name' => $municipalityName,
                'province_id' => $provinceId,
            ]);

            return null;
        }

        return $municipality->id;
    }

    protected function getDistrictId($regionId, $provinceId, $municipalityId, $districtName)
    {
        $region = Region::find($regionId);

        if (!$region) {
            throw new \Exception("Region with ID {$regionId} does not exist.");
        }

        $province = Province::find($provinceId);

        $query = District::where('name', $districtName)
            ->where('province_id', $provinceId);

        if ($region->name === "NCR" && $province->name !== 'Not Applicable') {
            if (empty($municipalityId)) {
                throw new \Exception("Municipality is required for districts in NCR.");
            }
            $query->where('municipality_id', $municipalityId);
        }

        $districtRecord = $query->first();

        if (!$districtRecord) {
            throw new \Exception("The district '{$districtName}' does not exist in the '{$province->name}' and '{$region->name}'");
        }

        return $districtRecord->id;
    }
}
