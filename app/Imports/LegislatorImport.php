<?php
namespace App\Imports;

use App\Models\Legislator;
use App\Models\Particular;
use App\Models\District;
use App\Models\Municipality;
use App\Models\Province;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LegislatorImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        // Validate the row data
        $validator = Validator::make($row, [
            'legislator' => 'required|string',
            'particular' => 'required|string',
            'district' => 'required|string',
            'municipality' => 'required|string',
            'province' => 'required|string',
        ]);

        // If validation fails, log a warning and skip this row
        if ($validator->fails()) {
            Log::warning("Validation failed for row: " . json_encode($row) . " with errors: " . json_encode($validator->errors()->all()));
            return null;
        }

        $legislatorName = $row['legislator'];
        $particularName = $row['particular'];
        $districtName = $row['district'];
        $municipalityName = $row['municipality'];
        $provinceName = $row['province'];

        // Find the existing province, municipality, and district
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

        // Find the particular associated with the district
        $particular = Particular::where([
            ['name', $particularName],
            ['district_id', $district->id],
        ])->first();

        if (!$particular) {
            Log::warning("No valid particular found for: " . $particularName . " in District: " . $districtName);
            return null;
        }

        // Find or create the legislator
        $legislator = Legislator::firstOrCreate(['name' => $legislatorName]);

        // Associate the legislator with the particular
        $legislator->particular()->syncWithoutDetaching([$particular->id]);

        return $legislator;
    }
}
