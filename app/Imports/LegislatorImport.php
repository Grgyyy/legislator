<?php

namespace App\Imports;

use App\Models\Legislator;
use App\Models\Particular;
use App\Models\District;
use App\Models\Municipality;
use App\Models\Province;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class LegislatorImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        // Perform validation
        $validator = Validator::make($row, [
            'legislator' => 'required|string',
            'particular' => 'required|string',
            'district' => 'required|string',
            'municipality' => 'required|string',
            'province' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("Validation failed for row: " . json_encode($row) . " with errors: " . json_encode($validator->errors()->all()));
            return null;
        }

        try {
            return DB::transaction(function () use ($row) {
                $legislatorName = $row['legislator'];
                $particularName = $row['particular'];
                $districtName = $row['district'];
                $municipalityName = $row['municipality'];
                $provinceName = $row['province'];

                $province = Province::where('name', $provinceName)->first();
                if (!$province) {
                    Log::warning("No valid province found for: " . $provinceName);
                    return null;
                }

                $municipality = Municipality::firstOrCreate([
                    'name' => $municipalityName,
                    'province_id' => $province->id,
                ]);

                $district = District::firstOrCreate([
                    'name' => $districtName,
                    'municipality_id' => $municipality->id,
                ]);

                $particular = Particular::where([
                    ['name', $particularName],
                    ['district_id', $district->id],
                ])->first();

                if (!$particular) {
                    Log::warning("No valid particular found for: " . $particularName . " in District: " . $districtName);
                    return null;
                }


                $legislator = Legislator::firstOrCreate(['name' => $legislatorName]);
                $legislator->particular()->syncWithoutDetaching([$particular->id]);

                return $legislator;
            });
        } catch (Throwable $e) {
            Log::error("An error occurred while importing row: " . json_encode($row) . " Error: " . $e->getMessage());
            throw $e;
        }
    }
}
