<?php

namespace App\Imports;

use Throwable;
use App\Models\Tvi;
use App\Models\Status;
use App\Models\Target;
use App\Models\TviType;
use App\Models\TviClass;
use App\Models\Allocation;
use App\Models\FundSource;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TargetImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        return DB::transaction(function () use ($row) {
            try {
                $fund_source_id = $this->getFundSourceId($row['fund_source']);
                $legislator_id = $this->getLegislatorId($row['legislator']);
                $soft_or_commitment_id = $this->getSoftOrCommitmentId($row['soft_or_commitment']);
                $appropriation_type = $row['appropriation_type'];
                $allocation_year_id = $this->getAllocationId($row['allocation']);
                $particular_id = $this->getParticularId($row['particular']);
                $district_id = $this->getDistrictId($row['district']);
                $municipality_id = $this->getMunicipalityId($row['municipality']);
                $province_id = $this->getProvinceId($row['province']);
                $region_id = $this->getRegionId($row['region']);
                $institution_id = $this->getInstitutionId($row['institution']);
                $tvi_type_id = $this->getTviTypeId($row['tvi_type']);
                $tvi_class_id = $this->getTviClassId($row['tvi_class']);
                $qualification_title_id = $this->getQualificationTitleId($row['qualification_title']);
                $scholarship_program_id = $this->getScholarshipProgramId($row['scholarship_program']);
                $status_id = $this->getStatusId($row['status']);

                return Target::create([
                    'fund_source_id' => $fund_source_id,
                    'legislator_id' => $legislator_id,
                    'soft_or_commitment_id' => $soft_or_commitment_id,
                    'appropriation_type' => $appropriation_type,
                    'allocation_year_id' => $allocation_year_id,
                    'particular_id' => $particular_id,
                    'district_id' => $district_id,
                    'municipality_id' => $municipality_id,
                    'province_id' => $province_id,
                    'region_id' => $region_id,
                    'institution_id' => $institution_id,
                    'tvi_type_id' => $tvi_type_id,
                    'tvi_class_id' => $tvi_class_id,
                    'qualification_title_id' => $qualification_title_id,
                    'scholarship_program_id' => $scholarship_program_id,
                    'number_of_slots' => $row['number_of_slots'],
                    'total_amount' => $row['total_amount'],
                    'status_id' => $status_id,
                ]);

            } catch (Throwable $e) {
                Log::error('Failed to import Targets: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    protected function getFundSourceId(string $fundSourceName)
    {
        $fund_source = FundSource::where('name', $fundSourceName)->first();
        if (!$fund_source) {
            throw new \Exception("Fund Source with name '{$fundSourceName}' not found. No changes were saved.");
        }
        return $fund_source->id;
    }

    protected function getLegislatorId(string $legislatorName)
    {
        $legislator = Legislator::where('name', $legislatorName)->first();
        if (!$legislator) {
            throw new \Exception("Legislator with name '{$legislatorName}' not found. No changes were saved.");
        }
        return $legislator->id;
    }

    protected function getSoftOrCommitmentId(string $softOrCommitmentName)
    {
        $softOrCommitment = Allocation::where('soft_or_commitment', $softOrCommitmentName)->first();
        if (!$softOrCommitment) {
            throw new \Exception("Soft or Commitment with name '{$softOrCommitmentName}' not found. No changes were saved.");
        }
        return $softOrCommitment->id;
    }

    protected function getAllocationId(int $allocationYear)
    {
        $allocation = Allocation::where('year', $allocationYear)->first();
        if (!$allocation) {
            throw new \Exception("Allocation with year '{$allocationYear}' not found. No changes were saved.");
        }
        return $allocation->id;
    }

    protected function getParticularId(string $particularName)
    {
        // Find an Allocation that has a Legislator whose associated Particular has the specified name.
        $allocation = Allocation::whereHas('legislator.particular', function ($query) use ($particularName) {
            $query->where('name', $particularName)
                ->whereHas('subParticular'); // Ensure that the particular has an associated sub-particular
        })->first();

        if (!$allocation) {
            throw new \Exception("Particular with name '{$particularName}' not found, or it is not associated with any Legislator having an Allocation and a SubParticular. No changes were saved.");
        }

        // Get the first matching particular associated with the legislator.
        $particular = $allocation->legislator->particular()->where('name', $particularName)->first();

        if (!$particular) {
            throw new \Exception("Particular with name '{$particularName}' not found for the associated Allocation. No changes were saved.");
        }

        return $particular->id;
    }




    protected function getDistrictId(string $districtName)
    {
        $tvi = Tvi::whereHas('district', function ($query) use ($districtName) {
            $query->where('name', $districtName);
        })->first();
        if (!$tvi) {
            throw new \Exception("District with name '{$districtName}' not found for the associated TVI. No changes were saved.");
        }
        return $tvi->district->id;
    }

    protected function getMunicipalityId(string $municipalityName)
    {
        $tvi = Tvi::whereHas('district.municipality', function ($query) use ($municipalityName) {
            $query->where('name', $municipalityName);
        })->first();
        if (!$tvi) {
            throw new \Exception("Municipality with name '{$municipalityName}' not found for the associated TVI. No changes were saved.");
        }
        return $tvi->district->municipality->id;
    }

    protected function getProvinceId(string $provinceName)
    {
        $tvi = Tvi::whereHas('district.municipality.province', function ($query) use ($provinceName) {
            $query->where('name', $provinceName);
        })->first();
        if (!$tvi) {
            throw new \Exception("Province with name '{$provinceName}' not found for the associated TVI. No changes were saved.");
        }
        return $tvi->district->municipality->province->id;
    }

    protected function getRegionId(string $regionName)
    {
        $tvi = Tvi::whereHas('district.municipality.province.region', function ($query) use ($regionName) {
            $query->where('name', $regionName);
        })->first();
        if (!$tvi) {
            throw new \Exception("Region with name '{$regionName}' not found for the associated TVI. No changes were saved.");
        }
        return $tvi->district->municipality->province->region->id;
    }

    protected function getInstitutionId(string $institutionName)
    {
        $institution = Tvi::where('name', $institutionName)->first();
        if (!$institution) {
            throw new \Exception("Institution with name '{$institutionName}' not found. No changes were saved.");
        }
        return $institution->id;
    }

    protected function getTviTypeId(string $tviTypeName)
    {
        $tviType = TviType::where('name', $tviTypeName)->first();
        if (!$tviType) {
            throw new \Exception("TVI Type with name '{$tviTypeName}' not found. No changes were saved.");
        }
        return $tviType->id;
    }

    protected function getTviClassId(string $tviClassName)
    {
        $tviClass = TviClass::where('name', $tviClassName)->first();
        if (!$tviClass) {
            throw new \Exception("TVI Class with name '{$tviClassName}' not found. No changes were saved.");
        }
        return $tviClass->id;
    }

    protected function getQualificationTitleId(string $qualificationName)
    {
        $qualification = QualificationTitle::whereHas('trainingProgram', function ($query) use ($qualificationName) {
            $query->where('title', $qualificationName);
        })->first();
        if (!$qualification) {
            throw new \Exception("Qualification with title '{$qualificationName}' not found. No changes were saved.");
        }
        return $qualification->id;
    }

    protected function getScholarshipProgramId(string $scholarshipProgramName)
    {
        $scholarshipProgram = ScholarshipProgram::where('name', $scholarshipProgramName)->first();
        if (!$scholarshipProgram) {
            throw new \Exception("Scholarship Program with name '{$scholarshipProgramName}' not found. No changes were saved.");
        }
        return $scholarshipProgram->id;
    }

    protected function getStatusId(string $statusName)
    {
        $status = Status::where('desc', $statusName)->first();
        if (!$status) {
            throw new \Exception("Status with description '{$statusName}' not found. No changes were saved.");
        }
        return $status->id;
    }
}
