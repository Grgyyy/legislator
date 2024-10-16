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
            // Validate the row first.
            if (!$this->validateRow($row)) {
                return null;
            }

            return DB::transaction(function () use ($row) {
                // Retrieve necessary IDs and data.
                $legislator_id = $this->getLegislatorId($row['legislator']);
                $particular_id = $this->getParticularId($row['particular']);
                $districtId = $this->getDistrictId($this->getMunicipalityId($this->getProvinceId($this->getRegionId($row['region']), $row['province']), $row['municipality']), $row['district']);
                $tvi = $this->getInstitutionData($row['institution'], $districtId);

                $year = $row['year'] ?? date('Y');
                $abdd_id = $this->getAbddId($row['abdd'] ?? null, $tvi->id);
                if (is_null($abdd_id)) {
                    Log::warning("ABDD ID is null for row: " . json_encode($row) . ". Skipping import for this row.");
                    return null;
                }

                $qualificationTitleId = $this->getQualificationTitleId($row['qualification_title']);
                if (is_null($qualificationTitleId)) {
                    Log::warning("Qualification Title not found for row: " . json_encode($row));
                    return null;
                }

                $numberOfSlots = $row['number_of_slots'] ?? 0;
                $qualificationTitle = QualificationTitle::find($qualificationTitleId);
                $costs = $this->calculateTotalAmount($qualificationTitle, $numberOfSlots);

                $scholarshipProgramId = $this->getScholarshipProgramId($row['scholarship_program']);
                $allocationId = $this->getAllocationId($row, $legislator_id, $particular_id, $year, $scholarshipProgramId);

                if (is_null($allocationId)) {
                    Log::warning("Allocation not found for row: " . json_encode($row) . ". Skipping import for this row.");
                    return null;
                }

                // Prepare data for creation.
                $targetData = [
                    'allocation_id' => $allocationId,
                    'legislator_id' => $legislator_id,
                    'tvi_id' => $tvi->id,
                    'abdd_id' => $abdd_id,
                    'scholarship_program_id' => $this->getScholarshipProgramId($row['scholarship_program']),
                    'qualification_title_id' => $qualificationTitleId,
                    'appropriation_type' => $row['appropriation_type'],
                    'year' => $year,
                    'number_of_slots' => $numberOfSlots,
                    'total_amount' => $costs['total_amount'],
                    'target_status_id' => 1,
                    // Include all the cost breakdown fields
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

                // Create the target and the history record.
                $target = Target::create($targetData);

                TargetHistory::create(array_merge($targetData, [
                    'target_id' => $target->id,
                    'description' => 'Target Created',
                ]));

                return $target;
            });
        } catch (Throwable $e) {
            Log::error("Import failed for row: " . json_encode($row) . ". Error: " . $e->getMessage());
            throw new \Exception("Import failed for row: " . json_encode($row) . ". Error: " . $e->getMessage());
        }
    }


    protected function validateRow(array $row)
    {
        $requiredFields = [
            'legislator' => 'Legislator name is required.',
            'particular' => 'Particular name is required.',
            'institution' => 'Institution name is required.',
            'scholarship_program' => 'Scholarship Program name is required.',
            'qualification_title' => 'Qualification Title is required.',
            'number_of_slots' => 'Number of slots is required.',
            'appropriation_type' => 'Appropriation type is required.',
            'abdd' => 'ABDD name is required.',
            'year' => 'Allocation year is required.',
        ];

        $missingFields = [];

        foreach ($requiredFields as $field => $errorMessage) {
            if (empty($row[$field])) {
                $missingFields[] = $errorMessage;
            }
        }

        if (!empty($missingFields)) {
            Log::warning("Missing required fields for row: " . json_encode($missingFields) . " in row: " . json_encode($row));
            return false;
        }

        if (!is_numeric($row['number_of_slots'])) {
            Log::warning("Invalid value for number of slots in row: " . json_encode($row));
            return false;
        }

        return true;
    }

    protected function getLegislatorId(string $legislatorName)
    {
        return Legislator::where('name', $legislatorName)->firstOrFail()->id;
    }

    protected function getFundSourceIdByLegislator(int $legislatorId)
    {
        return Allocation::with(['particular.subParticular.fundSource'])
            ->where('legislator_id', $legislatorId)
            ->firstOrFail()
            ->particular->subParticular->fundSource->id;
    }

    protected function getSoftOrCommitmentByLegislator(int $legislatorId)
    {
        return Allocation::where('legislator_id', $legislatorId)->firstOrFail()->soft_or_commitment;
    }

    protected function getParticularId(string $particularName)
    {
        $allocation = Allocation::whereHas('legislator.particular.subParticular', function ($query) use ($particularName) {
            $query->where('name', $particularName);
        })->firstOrFail();

        return $allocation->legislator->particular()->whereHas('subParticular', function ($query) use ($particularName) {
            $query->where('name', $particularName);
        })->firstOrFail()->id;
    }

    protected function getPartylistId(?string $partylistName)
    {
        if ($partylistName) {
            return Partylist::where('name', $partylistName)->firstOrFail()->id;
        }
        return null;
    }

    protected function getRegionId($regionName)
    {
        return Region::where('name', $regionName)->whereNull('deleted_at')->firstOrFail()->id;
    }

    protected function getProvinceId($regionId, $provinceName)
    {
        return Province::where('name', $provinceName)
            ->where('region_id', $regionId)
            ->whereNull('deleted_at')
            ->firstOrFail()->id;
    }

    protected function getMunicipalityId($provinceId, $municipalityName)
    {
        return Municipality::where('name', $municipalityName)
            ->where('province_id', $provinceId)
            ->whereNull('deleted_at')
            ->firstOrFail()->id;
    }

    protected function getDistrictId($municipalityId, $districtName)
    {
        return District::where('name', $districtName)
            ->where('municipality_id', $municipalityId)
            ->whereNull('deleted_at')
            ->firstOrFail()->id;
    }

    protected function getInstitutionData($institutionName, $districtId)
    {
        return Tvi::where('name', $institutionName)
            ->where('district_id', $districtId)
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    protected function getAbddId(?string $abddName, int $tvi_id)
    {
        // Log the input parameters
        Log::info('Attempting to retrieve ABDD ID', [
            'abddName' => $abddName,
            'tvi_id' => $tvi_id,
        ]);

        // If abddName is empty, log and handle accordingly
        if (empty($abddName)) {
            Log::warning('ABDD name is empty, setting abdd_id to null');
            return null; // Return null or a default value
        }

        try {
            // Find the TVI record to get the province_id
            $tvi_record = Tvi::findOrFail($tvi_id);
            $province_id = $tvi_record->district->municipality->province_id;

            // Use the many-to-many relationship to find the ABDD ID
            $abdd = Abdd::where('name', $abddName)
                ->whereHas('provinces', function ($query) use ($province_id) {
                    $query->where('provinces.id', $province_id); // Fully qualify the column
                })
                ->first();

            if (!$abdd) {
                Log::error("ABDD with name '$abddName' not found in province with ID $province_id.");
                throw new \Exception("ABDD with name '$abddName' not found in province with ID $province_id.");
            }

            Log::info('Successfully retrieved ABDD ID', ['abdd_id' => $abdd->id]);
            return $abdd->id; // Return the found ABDD ID
        } catch (Throwable $e) {
            Log::error("Error retrieving ABDD ID: " . $e->getMessage());
            return null; // Return null in case of error
        }
    }

    protected function getQualificationTitleId($qualificationTitleName)
    {
        Log::info("Looking for Qualification Title: " . $qualificationTitleName);

        $qualificationTitle = QualificationTitle::whereHas('trainingProgram', function ($query) use ($qualificationTitleName) {
            $query->where('title', $qualificationTitleName);
        })->first();

        if (!$qualificationTitle) {
            Log::warning("Qualification Title not found: " . $qualificationTitleName);
            return null; // or handle as needed
        }

        return $qualificationTitle->id;
    }



    protected function getScholarshipProgramId($scholarshipProgram)
    {
        return ScholarshipProgram::where('name', $scholarshipProgram)
            ->whereNull('deleted_at')
            ->first()->id ?? null;
    }

    protected function getAllocationId(array $row, int $legislatorId, int $particularId, int $allocationYear, int $scholarshipProgramId)
    {
        return Allocation::where('legislator_id', $legislatorId)
            ->where('particular_id', $particularId)
            ->where('year', $allocationYear)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->value('id');
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
