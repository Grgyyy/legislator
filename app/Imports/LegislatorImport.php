<?php

namespace App\Imports;

use App\Models\Legislator;
use App\Models\Particular;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LegislatorImport implements ToModel, WithHeadingRow
{

    use Importable;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model/null
     */



    public function model(array $row)
    {
        $particular_ids = self::getParticularIds($row['particular']);

        if (empty($particular_ids)) {
            Log::warning("No valid particulars found for: " . $row['particular']);
            return null;
        }

        $legislator = Legislator::create(['name' => $row['legislator']]);

        $legislator->particular()->syncWithoutDetaching($particular_ids);

        return $legislator;
    }

    public static function getParticularIds(string $particulars)
    {
        $particularNames = explode(',', $particulars);
        $particularIds = [];

        foreach (array_map('trim', $particularNames) as $name) {
            $particular = Particular::where('name', $name)->first();
            if ($particular) {
                $particularIds[] = $particular->id;
            } else {
                Log::warning("Particular not found: " . $name);
            }
        }

        return $particularIds;
    }

}
