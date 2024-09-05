<?php

namespace App\Imports;

use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\ScholarshipProgram;
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
                $legislator_id = $this->getLegislatorId($row['legislator']);
                $particular_id = $this->getParticularId($row['particular'], $row['district'], $row['municipality'], $row['province'], $legislator_id);
                $schopro_id = $this->getScholarshipProgramId($row['scholarship_program']);
                $allocation = $row['allocation'];
                $admin_cost = $allocation * 0.02;

                $allocationRecord = Allocation::where('legislator_id', $legislator_id)
                    ->where('particular_id', $particular_id)
                    ->where('scholarship_program_id', $schopro_id)
                    ->where('year', $row['year'])
                    ->first();

                if (!$allocationRecord) {
                    return Allocation::create([
                        'legislator_id' => $legislator_id,
                        'particular_id' => $particular_id,
                        'scholarship_program_id' => $schopro_id,
                        'allocation' => $allocation,
                        'admin_cost' => $admin_cost,
                        'balance' => $allocation - $admin_cost,
                        'year' => $row['year']
                    ]);
                } else {
                    $allocationRecord->update([
                        'allocation' => $allocation,
                        'admin_cost' => $admin_cost,
                        'balance' => $allocation - $admin_cost,
                    ]);

                    return $allocationRecord;
                }

            } catch (Throwable $e) {
                Log::error('Failed to import allocation: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['legislator', 'particular', 'scholarship_program', 'allocation', 'year'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("Validation error: The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }

        if (!is_numeric($row['allocation']) || $row['allocation'] <= 0) {
            throw new \Exception("Validation error: The field 'allocation' must be a positive number. No changes were saved.");
        }

        if (!is_numeric($row['year']) || $row['year'] < 2000 || $row['year'] < date('Y')) {
            throw new \Exception("Validation error: The field 'year' must be a valid year. No changes were saved.");
        }
    }

    protected function getLegislatorId(string $legislatorName)
    {
        $legislator = Legislator::where('name', $legislatorName)
            ->first();

        if (!$legislator) {
            throw new \Exception("Legislator with name '{$legislatorName}' not found. No changes were saved.");
        }

        return $legislator->id;
    }

    protected function getParticularId(string $particularName, string $districtName, string $municipalityName, string $provinceName, string $legislator_id)
    {
        $legislator = Legislator::find($legislator_id);

        if (!$legislator) {
            throw new \Exception("Legislator with ID '{$legislator_id}' not found. No changes were saved.");
        }

        $particular = Particular::where('name', $particularName)
            ->whereHas('district', function ($query) use ($districtName, $municipalityName, $provinceName) {
                $query->where('districts.name', $districtName)
                    ->whereHas('municipality', function ($query) use ($municipalityName) {
                        $query->where('municipalities.name', $municipalityName);
                    })
                    ->whereHas('municipality.province', function ($query) use ($provinceName) {
                        $query->where('provinces.name', $provinceName);
                    })
                    ->whereNull('districts.deleted_at');
            })
            ->whereHas('legislator', function ($query) use ($legislator_id) {
                $query->where('legislators.id', $legislator_id)
                    ->whereNull('legislators.deleted_at');
            })
            ->whereNull('particulars.deleted_at')
            ->first();

        if (!$particular) {
            throw new \Exception("Particular with name '{$particularName}' and district '{$districtName}' not found for the given legislator.");
        }

        return $particular->id;
    }


    protected function getScholarshipProgramId(string $schoproName)
    {
        $scholarship = ScholarshipProgram::where('name', $schoproName)
            ->first();

        if (!$scholarship) {
            throw new \Exception("Scholarship program with name '{$schoproName}' not found. No changes were saved.");
        }

        return $scholarship->id;
    }
}
