<?php

namespace App\Imports;

use Throwable;
use App\Models\Target;
use App\Models\Legislator;
use App\Models\Tvi;
use App\Models\Allocation;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\TargetStatus;
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
        // Log the incoming row data for debugging
        Log::info('Processing row:', $row);

        try {
            // Validate required fields
            $this->validateRow($row);

            return DB::transaction(function () use ($row) {
                $legislator_id = $this->getLegislatorId($row['legislator']);
                $institutionData = $this->getInstitutionData($row['institution'], $row['district'], $row['municipality'], $row['province'], $row['region']);

                // $tvi_id = $institutionData['tvi_id'];
                $tvi_id = $institutionData->id;


                $appropriation_type = $row['appropriation_type'];
                $allocation_year_id = $this->getAllocationId($row['allocation']);
                $particular_id = $this->getParticularId($row['particular']);
                $qualification_title_id = $this->getQualificationTitleId($row['qualification_title']);
                $scholarship_program_id = $this->getScholarshipProgramId($row['scholarship_program']);
                $status_id = $this->getStatusId($row['status']);
                $allocation_id = $this->getAllocationId($row['allocation']);

                return Target::create([
                    'fund_source_id' => $this->getFundSourceIdByLegislator($legislator_id),
                    'legislator_id' => $legislator_id,
                    'soft_or_commitment_id' => $this->getSoftOrCommitmentByLegislator($legislator_id),
                    'appropriation_type' => $appropriation_type,
                    'allocation_year_id' => $allocation_year_id,
                    'allocation_id' => $allocation_id,
                    'particular_id' => $particular_id,


                    // 'district_id' => $institutionData['district_id'],
                    // 'municipality_id' => $institutionData['municipality_id'],
                    // 'province_id' => $institutionData['province_id'],
                    // 'region_id' => $institutionData['region_id'],
                    // 'tvi_type_id' => $institutionData['tvi_type_id'],
                    // 'tvi_class_id' => $institutionData['tvi_class_id'],


                    'tvi_id' => $tvi_id, // Add this line
                    'qualification_title_id' => $qualification_title_id,
                    'scholarship_program_id' => $scholarship_program_id,
                    'number_of_slots' => $row['no_of_slots'],
                    'total_amount' => $row['total_amount'],
                    'status_id' => $status_id,
                ]);


            });



        } catch (Throwable $e) {
            $this->logImportError($e, $row); // Log error with row data
            throw new \Exception("Import failed for row: " . json_encode($row) . ". Error: " . $e->getMessage());
        }
    }

    protected function validateRow(array $row)
    {
        $requiredColumns = [
            'legislator',
            'particular',
            'appropriation_type',
            'allocation',
            'institution',
            'qualification_title',
            'scholarship_program',
            'no_of_slots',
            'total_amount',
            'status'
        ];

        foreach ($requiredColumns as $column) {
            if (empty($row[$column])) {
                throw new \Exception("Missing value for '{$column}'. Row data: " . json_encode($row));
            }
        }
    }

    protected function logImportError(Throwable $e, array $row)
    {
        Log::error('Failed to import Targets: ' . $e->getMessage(), [
            'row_data' => $row,
            'error_trace' => $e->getTraceAsString()
        ]);
    }
    protected function getInstitutionData(string $institutionName, $districtName, $municipalityName, $provinceName, $regionName)
    {
        $institution = Tvi::where('name', $institutionName)
            ->where('district_id', $districtName)
            ->where('district.municipality', $municipalityName)
            ->where('district.municipality.province', $provinceName)
            ->where('district.municipality.province.region', $regionName)
            ->firstOrFail();

        if (!$institution) {
            throw new \Exception("No Institution found for Institution name: {$institutionName}");
        }

        return $institution;



        // with([
        //     'district',
        //     'district.municipality',
        //     'district.municipality.province',
        //     'district.municipality.province.region',
        //     'tviClass.tviType'
        // ])
        // where('name', $institutionName)
        //     ->where('name', $institutionName)
        //     ->where('name', $institutionName)
        //     ->where('name', $institutionName)
        //     ->where('name', $institutionName)
        //     ->firstOrFail();

        // return [
        //     'tvi_id' => $institution->id,
        //     'district_id' => $institution->district->id,
        //     'municipality_id' => $institution->district->municipality->id,
        //     'province_id' => $institution->district->municipality->province->id,
        //     'region_id' => $institution->district->municipality->province->region->id,
        //     'tvi_type_id' => $institution->tviClass->tviType->id,
        //     'tvi_class_id' => $institution->tvi_class_id,
        // ];
    }



    protected function getLegislatorId(string $legislatorName)
    {
        return Legislator::where('name', $legislatorName)->firstOrFail()->id;
    }
    protected function getFundSourceIdByLegislator(int $legislatorId)
    {
        $allocation = Allocation::with(['particular.subParticular.fundSource'])
            ->where('legislator_id', $legislatorId)
            ->first();

        Log::info('Fetching fund source for legislator', ['legislator_id' => $legislatorId, 'allocation' => $allocation]);

        if (!$allocation) {
            throw new \Exception("No allocation found for legislator ID: {$legislatorId}");
        }

        $subParticular = $allocation->particular->subParticular;
        $fundSource = $subParticular ? $subParticular->fundSource : null;

        if (!$fundSource) {
            Log::error('No associated Fund Source found', ['legislator_id' => $legislatorId, 'allocation_id' => $allocation->id]);
            throw new \Exception("No associated Fund Source found for legislator ID: {$legislatorId}");
        }

        Log::info('Fund Source found', ['fund_source_id' => $fundSource->id]);

        return $fundSource->id;
    }



    protected function getSoftOrCommitmentByLegislator(int $legislatorId)
    {
        // Assuming Legislator model has a relationship to Allocation that has soft_or_commitment
        $allocation = Allocation::where('legislator_id', $legislatorId)->firstOrFail();
        return $allocation->soft_or_commitment; // Adjust if you need an ID or if the column is different
    }

    protected function getAllocationId(string $allocationYear)
    {
        return Allocation::where('year', $allocationYear)->firstOrFail()->id;
    }

    protected function getParticularId(string $particularName)
    {
        Log::info("Searching for Particular with name '{$particularName}' linked to a legislator with an allocation.");

        $allocation = Allocation::whereHas('legislator.particular.subParticular', function ($query) use ($particularName) {
            $query->where('name', $particularName);
        })->first();

        if (!$allocation) {
            throw new \Exception("Particular with name '{$particularName}' not found, or it is not associated with any Legislator having an Allocation and a SubParticular. No changes were saved.");
        }

        $legislator = $allocation->legislator;

        if (!$legislator) {
            throw new \Exception("No Legislator found for the Allocation. No changes were saved.");
        }

        $particular = $legislator->particular()->whereHas('subParticular', function ($query) use ($particularName) {
            $query->where('name', $particularName);
        })->first();

        if (!$particular) {
            throw new \Exception("Particular with name '{$particularName}' not found for the associated Legislator. No changes were saved.");
        }

        return $particular->id;
    }

    protected function getQualificationTitleId(string $qualificationName)
    {
        $qualification = QualificationTitle::whereHas('trainingProgram', function ($query) use ($qualificationName) {
            $query->where('title', $qualificationName);
        })->firstOrFail();
        return $qualification->id;
    }

    protected function getScholarshipProgramId(string $scholarshipProgramName)
    {
        $scholarshipProgram = ScholarshipProgram::where('name', $scholarshipProgramName)->firstOrFail();
        return $scholarshipProgram->id;
    }

    protected function getStatusId(string $statusName)
    {
        Log::info('Searching for Status with desc', ['status_name' => trim($statusName)]);

        $status = TargetStatus::where('desc', trim($statusName))->first();

        if (!$status) {
            Log::error('Status not found', ['status_name' => $statusName]);
            throw new \Exception("There was an issue importing the Status: Status '{$statusName}' not found.");
        }

        Log::info('Status found', ['status_id' => $status->id, 'status_name' => $statusName]);

        return $status->id;
    }



}
