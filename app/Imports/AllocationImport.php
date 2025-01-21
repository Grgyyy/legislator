<?php

namespace App\Imports;

use App\Models\Allocation;
use App\Models\District;
use App\Models\Legislator;
use App\Models\Municipality;
use App\Models\Particular;
use App\Models\Partylist;
use App\Models\Province;
use App\Models\Region;
use App\Models\ScholarshipProgram;
use App\Models\SubParticular;
use App\Services\NotificationHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class AllocationImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                // Get legislator and attributor details (attributor_id may be null)
                $legislatorRecord = $this->getLegislatorId($row['legislator']);
                $attributorRecord = $row['attributor'] ? $this->getLegislatorId($row['attributor']) : null;
                $subParticularRecord = $this->getSubParticularName($row['particular']);
                $attributorSubParticularRecord = $row['attributor_particular'] ? $this->getSubParticularName($row['attributor_particular']) : null;
                $partylistRecord = $this->getPartylist($row['partylist']);
                $districtRecord = $this->getDistrict($row);
                $particularRecord = $this->getParticularRecord($subParticularRecord->id, $partylistRecord->id, $districtRecord->id);
                $attributorParticularRecord = $row['attributor_particular'] ? $this->getParticularRecord($attributorSubParticularRecord->id, $partylistRecord->id, $districtRecord->id) : null;
                $scholarshipProgramRecord = $this->getScholarshipProgram($row['scholarship_program']);
                $allocation = $row['allocation'];
                $adminCost = $allocation * 0.02;

                // Check if the allocation already exists (handle attributor_id properly)
                $allocationRecord = Allocation::where('legislator_id', $legislatorRecord->id)
                    ->where('attributor_id', $attributorRecord ? $attributorRecord->id : null)  
                    ->where('attributor_particular_id', $attributorParticularRecord ? $attributorParticularRecord->id : null)  
                    ->where('particular_id', $particularRecord->id)
                    ->where('scholarship_program_id', $scholarshipProgramRecord->id)
                    ->where('soft_or_commitment', $row['soft_or_commitment'])
                    ->where('year', $row['year'])
                    ->first();

                if ($allocationRecord) {
                    $message = $allocationRecord->deleted_at
                        ? 'This allocation with the provided details has been deleted and must be restored before reuse.'
                        : 'This Allocation with the provided details already exists.';

                    throw new \Exception($message);
                } else {
                    // Create a new allocation if not found
                    $allocationRecord = Allocation::create([
                        'soft_or_commitment' => $row['soft_or_commitment'],
                        'attributor_id' => $attributorRecord ? $attributorRecord->id : null, // Only assign if not null
                        'legislator_id' => $legislatorRecord->id,
                        'particular_id' => $particularRecord->id,
                        'attributor_particular_id' => $attributorParticularRecord ? $attributorParticularRecord->id : null,
                        'scholarship_program_id' => $scholarshipProgramRecord->id,
                        'allocation' => $allocation,
                        'admin_cost' => $adminCost,
                        'balance' => $allocation - $adminCost,
                        'year' => $row['year'],
                    ]);
                }

            } catch (Throwable $e) {
                Log::error('Failed to import allocation: ' . $e->getMessage());
                throw $e;
            }
        });
    }


    protected function validateRow(array $row)
    {
        $requiredFields = ['legislator', 'soft_or_commitment', 'particular', 'partylist', 'district', 'municipality', 'province', 'region', 'scholarship_program', 'allocation', 'year'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("Validation error: The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }

        if (!is_numeric($row['allocation']) || $row['allocation'] <= 0) {
            throw new \Exception("Validation error: The field 'allocation' must be a positive number. No changes were saved.");
        }

        if ($row['attributor'] != null) {
            if($row['legislator'] === $row['attributor']) {
                throw new \Exception("Validation error: The field 'legislator' and 'attribution' must be different. No changes were saved.");
            }
        }
    }

    protected function getLegislatorId(string $legislatorName)
    {
        $legislator = Legislator::where('name', $legislatorName)
            ->first();

        if (!$legislator) {
            throw new \Exception("The Legislator named '{$legislatorName}' is not existing.");
        }

        return $legislator;
    }

    protected function getSubParticularName(string $subParticularName) {
        $subParticular = SubParticular::where('name', $subParticularName)
            ->whereNull('deleted_at')
            ->first();

        if(!$subParticular) {
            throw new \Exception("The Partcular named '{$subParticularName}' is not existing.");
        }

        return $subParticular;
    }

    protected function getPartylist(string $partylistName) {
        $partylist = Partylist::where('name', $partylistName)
            ->whereNull('deleted_at')
            ->first();

        if(!$partylist) {
            throw new \Exception("The Partylist named '{$partylistName}' is not existing.");
        }

        return $partylist;
    }

    protected function getDistrict(array $row) {
        $region = Region::where('name', $row['region'])
        ->whereNull('deleted_at')
        ->first();

        if (!$region) {
            throw new \Exception("The Region named '{$row['region']}' is not existing.");
        }

        $province = Province::where('name', $row['province'])
            ->where('region_id', $region->id)
            ->whereNull('deleted_at')
            ->first();
        
        if (!$province) {
            throw new \Exception("The Province named '{$row['province']}' is not existing.");
        }

        $districtQuery = District::where('name', $row['district'])
            ->where('province_id', $province->id)
            ->whereNull('deleted_at');
    
        if($row['particular'] === 'District') {
            if($row['region'] === 'NCR') {
                $municipality = Municipality::where('name', $row['municipality'])
                    ->where('province_id', $province->id)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$municipality) {
                    throw new \Exception("The Municipality named '{$row['municipality']}' is not existing.");
                }

                $districtQuery->where('municipality_id', $municipality->id);
            }
        }

        $district = $districtQuery->first();

        if (!$province) {
            throw new \Exception("The District named '{$row['district']}' under Province named '{$row['province']}' is not existing.");
        }

        return $district;
        
    }

    protected function getParticularRecord(int $subParticularId, int $partylistId, int $districtId) {
        $particular = Particular::where('sub_particular_id', $subParticularId)
            ->where('partylist_id', $partylistId)
            ->where('district_id', $districtId)
            ->first();

        if (!$particular) {
            throw new \Exception("The Particular named '{$particular->subParticular->name}' is not existing.");
        }

        return $particular;
    }

    protected function getScholarshipProgram(string $scholarshipProgramName) {
        $scholarshipProgram = ScholarshipProgram::where('name', $scholarshipProgramName)
            ->first();

        if (!$scholarshipProgram) {
            throw new \Exception("The Scholarship Program named '{$scholarshipProgramName}' is not existing.");
        }

        return $scholarshipProgram;
    }

    
}
