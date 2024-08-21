<?php

namespace App\Imports;

use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\LegislatorParticular;
use App\Models\Particular;
use App\Models\ScholarshipProgram;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AllocationImport implements ToModel, WithHeadingRow
{
    use Importable;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $legislator_id = self::getLegislatorId($row['legislator']);
        $particular_id = self::getParticularId($legislator_id, $row['particular']);
        $scholarship_program_id = self::getScholarshipProgramId($row['scholarship_program']);

        $allocation = Allocation::create([
            'legislator_id' => $legislator_id,
            'particular_id' => $particular_id,
            'scholarship_program_id' => $scholarship_program_id,
            'allocation' => $row['allocation'],
            'admin_cost' => $row['admin_cost'],
        ]);

        // Optional: If these relationships exist and need to be synced
        $allocation->particular()->syncWithoutDetaching([$particular_id]);
        $allocation->legislator()->syncWithoutDetaching([$legislator_id]);
        $allocation->scholarship_program()->syncWithoutDetaching([$scholarship_program_id]);

        return $allocation;
    }

    public static function getLegislatorId(string $legislator)
    {
        $legislatorRecord = Legislator::where('name', $legislator)->first();

        return $legislatorRecord->id;

    }

    public static function getParticularId(int $legislator, string $particular)
    {
        // $particularRecord = LegislatorParticular::where('name', $particular)
        //     ->where('legislator_id', $legislator)
        //     ->first();
        // if ($particularRecord) {
        //     return $particularRecord->id;
        // }

        // // Handle the case where no particular is found (return null or handle as needed)
        // return null;


        // $LegislatorRecords = LegislatorParticular::where('legislator_id', $legislator)->get();

        $ParticularRecords = Particular::where('name', $particular)
            ->get();
        $legislatorRecords = LegislatorParticular::where('legislator_id', $legislator)
            ->whereIn('particular_id', $ParticularRecords)
            ->get();


    }

    public static function getScholarshipProgramId(string $scholarshipProgram)
    {
        $scholarshipProgramRecord = ScholarshipProgram::where('name', $scholarshipProgram)->first();

        return $scholarshipProgramRecord->id;

    }
}

