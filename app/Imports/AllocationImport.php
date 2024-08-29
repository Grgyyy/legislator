<?php

namespace App\Imports;

use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\ScholarshipProgram;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AllocationImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $legislator_id = $this->getLegislatorId($row['legislator']);
        $particular_id = $this->getParticularId($row['particular']);
        $schopro_id = $this->getSchoproId($row['scholarship_program']);
        $allocation = $row['allocation'];
        $admin_cost = $allocation * 0.02;

        return new Allocation([
            'legislator_id' => $legislator_id,
            'particular_id' => $particular_id,
            'scholarship_program_id' => $schopro_id,
            'allocation' => $allocation,
            'admin_cost' => $admin_cost,
            'balance' => $allocation,
            'year' => $row['year']
        ]);
    }

    private function getLegislatorId(string $legislatorName)
    {
        $legislator = Legislator::where('name', $legislatorName)
            ->firstOrFail();
        return $legislator->id;
    }

    private function getParticularId(string $particularName)
    {
        $particular = Particular::where('name', $particularName)
            ->firstOrFail();
        return $particular->id;
    }

    private function getSchoproId(string $schoproName)
    {
        $scholarship = ScholarshipProgram::where('name', $schoproName)
            ->firstOrFail();
        return $scholarship->id;
    }
}


