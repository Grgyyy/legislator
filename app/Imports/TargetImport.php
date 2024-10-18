<?php

namespace App\Imports;

use Throwable;
use App\Models\Tvi;
use App\Models\Abdd;
use App\Models\Region;
use App\Models\Target;
use App\Models\District;
use App\Models\Province;
use App\Models\Partylist;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\Municipality;
use App\Models\TargetHistory;
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
        try {
            $this->validateRow($row);
            $this->validateNumberOfSlots($row['number_of_slots']);
            $this->validateYear($row['year']);

            DB::transaction(function () use ($row) {
                $legislator_id = $this->getLegislatorId($row['legislator']);
                $particular_id = $this->getParticularId($row['particular']);

                $districtId = $this->getDistrictId($this->getMunicipalityId($this->getProvinceId($this->getRegionId($row['region']), $row['province']), $row['municipality']), $row['district']);
                $tvi = $this->getInstitutionData($row['institution'], $districtId);

                $year = $row['year'] ?? date('Y');
                $abdd_id = $this->getAbddId($row['abdd_sector'], $tvi->id);

                $qualificationTitleId = $this->getQualificationTitleId($row['qualification_title']);
                $numberOfSlots = $row['number_of_slots'];
                $scholarshipProgramId = $this->getScholarshipProgramId($row['scholarship_program']);

                $allocation = $this->getAllocationId($row, $legislator_id, $particular_id, $year, $scholarshipProgramId);

                $costs = $this->getQualificationCosts($qualificationTitleId, $numberOfSlots);

                $targetData = [
                    'allocation_id' => $allocation->id,
                    'legislator_id' => $legislator_id,
                    'tvi_id' => $tvi->id,
                    'abdd_id' => $abdd_id,
                    'scholarship_program_id' => $scholarshipProgramId,
                    'qualification_title_id' => $qualificationTitleId,
                    'appropriation_type' => $row['appropriation_type'],
                    'year' => $year,
                    'number_of_slots' => $numberOfSlots,
                    'total_amount' => $costs['total_amount'],
                    'target_status_id' => 1,
                    'total_training_cost_pcc' => $costs['total_training_cost_pcc'],
                    'total_cost_of_toolkit_pcc' => $costs['total_cost_of_toolkit_pcc'],
                    'total_training_support_fund' => $costs['total_training_support_fund'],
                    'total_assessment_fee' => $costs['total_assessment_fee'],
                    'total_entrepreneurship_fee' => $costs['total_entrepreneurship_fee'],
                    'total_new_normal_assistance' => $costs['total_new_normal_assistance'],
                    'total_accident_insurance' => $costs['total_accident_insurance'],
                    'total_book_allowance' => $costs['total_book_allowance'],
                    'total_uniform_allowance' => $costs['total_uniform_allowance'],
                    'total_misc_fee' => $costs['total_misc_fee'],
                ];

                $target = Target::create($targetData);

                TargetHistory::create(array_merge($targetData, [
                    'target_id' => $target->id,
                    'description' => 'Target Created',
                ]));

                $allocation->balance -= $costs['total_amount'];
                $allocation->save();
            });
        } catch (Throwable $e) {
            Log::error("Import failed: " . $e->getMessage());
            throw $e;
        }
    }


    protected function validateRow(array $row)
    {
        $requiredFields = [
            'legislator',
            'particular',
            'institution',
            'partylist',
            'district',
            'municipality',
            'province',
            'region',
            'scholarship_program',
            'qualification_title',
            'abdd_sector',
            'appropriation_type',
            'year',
            'number_of_slots',
        ];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function validateNumberOfSlots(int $number_of_slots)
    {
        if ($number_of_slots < 10 || $number_of_slots > 25) {
            throw new \Exception("The field '{$number_of_slots}' in Number of Slot should be greater than or equal to 10 and less than equal to 25 ");
        }
    }

    protected function validateYear(int $year)
    {
        $currentYear = date('Y');
        $pastYear = $currentYear - 1;
        if ($year != $currentYear && $year != $pastYear) {
            throw new \Exception("The provided year '{$year}' must be either the current year '{$currentYear}' or the previous year '{$pastYear}'.");
        }
    }


    protected function getLegislatorId(string $legislatorName)
    {
        $legislator = Legislator::where('name', $legislatorName)->first();

        if (!$legislator) {
            throw new \Exception("Legislator not found for name: {$legislatorName}");
        }

        return $legislator->id;
    }


    protected function getParticularId(string $particularName)
    {
        $allocation = Allocation::whereHas('legislator.particular.subParticular', function ($query) use ($particularName) {
            $query->where('name', $particularName);
        })->first();

        if (!$allocation) {
            throw new \Exception("Allocation with particular name '{$particularName}' not found.");
        }

        $particular = $allocation->legislator->particular()->whereHas('subParticular', function ($query) use ($particularName) {
            $query->where('name', $particularName);
        })->first();

        if (!$particular) {
            throw new \Exception("Particular with name '{$particularName}' not found for legislator ID: " . $allocation->legislator_id);
        }

        return $particular->id;
    }
    protected function getFundSourceIdByLegislator(int $legislatorId)
    {
        $allocation = Allocation::with(['particular.subParticular.fundSource'])
            ->where('legislator_id', $legislatorId)
            ->first();

        if (!$allocation || !$allocation->particular || !$allocation->particular->subParticular || !$allocation->particular->subParticular->fundSource) {
            throw new \Exception("Fund source not found for legislator ID: " . $legislatorId);
        }

        return $allocation->particular->subParticular->fundSource->id;
    }

    protected function getSoftOrCommitmentByLegislator(int $legislatorId)
    {
        $allocation = Allocation::where('legislator_id', $legislatorId)->first();

        if (!$allocation) {
            throw new \Exception("Allocation not found for legislator ID: " . $legislatorId);
        }

        return $allocation->soft_or_commitment;
    }

    protected function getPartylistId(?string $partylistName)
    {

        $partylist = Partylist::where('name', $partylistName)->first();

        if (!$partylist) {
            throw new \Exception("Partylist not found for name: {$partylistName}");
        }

        return $partylist->id;
    }

    protected function getDistrictId($municipalityId, $districtName)
    {
        $district = District::where('name', $districtName)
            ->where('municipality_id', $municipalityId)
            ->whereNull('deleted_at')
            ->first();

        if (!$district) {
            throw new \Exception($districtName, $municipalityId);
        }

        return $district->id;
    }

    protected function getMunicipalityId($provinceId, $municipalityName)
    {
        $municipality = Municipality::where('name', $municipalityName)
            ->where('province_id', $provinceId)
            ->whereNull('deleted_at')
            ->first();

        if (!$municipality) {
            throw new \Exception($municipalityName, $provinceId);
        }

        return $municipality->id;
    }
    protected function getProvinceId($regionId, $provinceName)
    {
        $province = Province::where('name', $provinceName)
            ->where('region_id', $regionId)
            ->whereNull('deleted_at')
            ->first();

        if (!$province) {
            throw new \Exception($provinceName, $regionId);
        }
        return $province->id;
    }
    protected function getRegionId(string $regionName)
    {
        $region = Region::where('name', $regionName)
            ->whereNull('deleted_at')
            ->first();

        if (!$region) {
            throw new \Exception("Region not found for name: {$regionName}");
        }

        return $region->id;
    }

    protected function getInstitutionData($institutionName, $districtId)
    {
        $institution = Tvi::where('name', $institutionName)
            ->where('district_id', $districtId)
            ->whereNull('deleted_at')
            ->first();

        if (!$institution) {
            throw new \Exception($institutionName, $districtId);
        }

        return $institution;
    }

    protected function getQualificationTitleId($qualificationTitleName)
    {
        $qualificationTitle = QualificationTitle::whereHas('trainingProgram', function ($query) use ($qualificationTitleName) {
            $query->where('title', $qualificationTitleName);
        })->first();

        if (!$qualificationTitle) {
            throw new \Exception($qualificationTitleName);
        }

        return $qualificationTitle->id;
    }

    protected function getAbddId(string $abddName, int $tvi_id)
    {

        $tvi_record = Tvi::findOrFail($tvi_id);
        $province_id = $tvi_record->district->municipality->province_id;

        $abdd = Abdd::where('name', $abddName)
            ->whereHas('provinces', function ($query) use ($province_id) {
                $query->where('provinces.id', $province_id);
            })
            ->first();

        if (!$abdd) {
            throw new \Exception("ABDD with name '$abddName' not found in province with ID $province_id.");
        }

        return $abdd->id;
    }
    protected function getScholarshipProgramId($scholarshipProgram)
    {
        $scholarship = ScholarshipProgram::where('name', $scholarshipProgram)
            ->whereNull('deleted_at')
            ->first();

        if (!$scholarship) {
            throw new \Exception($scholarshipProgram);
        }

        return $scholarship->id;
    }

    protected function getAllocationId(array $row, int $legislator_id, int $particular_id, int $year, int $scholarship_program_id)
    {
        $allocationId = Allocation::where('legislator_id', $legislator_id)
            ->where('particular_id', $particular_id)
            ->where('year', $year)
            ->where('scholarship_program_id', $scholarship_program_id)
            ->first();
        if (!$allocationId) {
            throw new \Exception("Allocation not found for legislator ID {$legislator_id}, particular ID {$particular_id}, year {$year}, and scholarship program ID {$scholarship_program_id}.");
        }

        return $allocationId;
    }

    protected function getQualificationCosts(int $qualificationTitleId, int $numberOfSlots)
    {
        $qualificationTitle = QualificationTitle::find($qualificationTitleId);
        if (!$qualificationTitle) {
            throw new \Exception("Qualification Title with ID {$qualificationTitleId} not found.");
        }
        return $this->calculateTotalAmount($qualificationTitle, $numberOfSlots);
    }


    protected function calculateTotalAmount(QualificationTitle $qualificationTitle, int $numberOfSlots)
    {
        $total_training_cost_pcc = $qualificationTitle->training_cost * $numberOfSlots;
        $total_cost_of_toolkit_pcc = $qualificationTitle->cost_of_toolkit * $numberOfSlots;
        $total_training_support_fund = $qualificationTitle->training_support_fund * $numberOfSlots;
        $total_assessment_fee = $qualificationTitle->assessment_fee * $numberOfSlots;
        $total_entrepreneurship_fee = $qualificationTitle->entrepreneurship_fee * $numberOfSlots;
        $total_new_normal_assistance = $qualificationTitle->new_normal_assistance * $numberOfSlots;
        $total_accident_insurance = $qualificationTitle->accident_insurance * $numberOfSlots;
        $total_book_allowance = $qualificationTitle->book_allowance * $numberOfSlots;
        $total_uniform_allowance = $qualificationTitle->uniform_allowance * $numberOfSlots;
        $total_misc_fee = $qualificationTitle->misc_fee * $numberOfSlots;

        return [
            'total_amount' => $total_training_cost_pcc +
                $total_cost_of_toolkit_pcc +
                $total_training_support_fund +
                $total_assessment_fee +
                $total_entrepreneurship_fee +
                $total_new_normal_assistance +
                $total_accident_insurance +
                $total_book_allowance +
                $total_uniform_allowance +
                $total_misc_fee,
            'total_training_cost_pcc' => $total_training_cost_pcc,
            'total_cost_of_toolkit_pcc' => $total_cost_of_toolkit_pcc,
            'total_training_support_fund' => $total_training_support_fund,
            'total_assessment_fee' => $total_assessment_fee,
            'total_entrepreneurship_fee' => $total_entrepreneurship_fee,
            'total_new_normal_assistance' => $total_new_normal_assistance,
            'total_accident_insurance' => $total_accident_insurance,
            'total_book_allowance' => $total_book_allowance,
            'total_uniform_allowance' => $total_uniform_allowance,
            'total_misc_fee' => $total_misc_fee,
        ];
    }
}
