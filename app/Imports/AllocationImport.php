<?php

namespace App\Imports;

use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\ScholarshipProgram;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Models\LegislatorParticular;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Validation\ValidationException;

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
        try {
            $data = Validator::make($row, [
                'allocation' => 'required|regex:/^\d+(\.\d{1})?$/',
                'admin_cost' => 'required|regex:/^\d+(\.\d{1})?$/',
            ])->validate();

            $particular_id = self::getParticularId($row['particular']);
            $legislator_id = self::getLegislatorId($particular_id, $row['legislator']);
            $schopro_id = self::getSpId($row['scholarship_program']);

            return new Allocation([
                'legislator_id' => $legislator_id,
                'particular_id' => $particular_id,
                'scholarship_program_id' => $schopro_id,
                'allocation' => $data['allocation'],
                'admin_cost' => $data['admin_cost'],
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed for row: ' . json_encode($row) . ' - Errors: ' . json_encode($e->errors()));
            return null;
        }
    }

    public static function getParticularId(string $particular)
    {
        return Particular::where('name', $particular)
            ->first()
            ->id;
    }
    // public static function getLegislatorId(int $particularId, string $legislatorName)
    // {
    //     return Legislator::where('name', $legislatorName)
    //         ->where('particular_id', $particularId)
    //         ->first()
    //         ->id;
    // }

    public static function getLegislatorId(int $particularId, string $legislatorName)
    {
        return Legislator::where('name', $legislatorName)
            ->whereHas('particular', function ($query) use ($particularId) {
                $query->where('particular_id', $particularId);
            })
            ->first()
            ->id;
    }


    public static function getSpId(string $SchoproName)
    {
        return ScholarshipProgram::where('name', $SchoproName)
            ->first()
            ->id;
    }

}

