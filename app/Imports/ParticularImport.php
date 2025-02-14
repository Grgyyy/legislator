<?php
namespace App\Imports;

use App\Models\District;
use App\Models\Municipality;
use App\Models\Particular;
use App\Models\Partylist;
use App\Models\Province;
use App\Models\Region;
use App\Models\SubParticular;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class ParticularImport implements ToModel, WithHeadingRow
{
    use Importable;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $subParticularRecord = $this->getSubParticularName($row['particular']);
                $partylistRecord = $this->getPartylist($row['partylist']);
                $districtRecord = $this->getDistrict($row);

                $particularExists = Particular::where('sub_particular_id', $subParticularRecord->id)
                    ->where('district_id', $districtRecord->id)
                    ->where('partylist_id', $partylistRecord->id)
                    ->exists();

                if (!$particularExists) {
                    return new Particular([
                        'sub_particular_id' => $subParticularRecord->id,
                        'partylist_id' => $partylistRecord->id,
                        'district_id' => $districtRecord->id,
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('Failed to import particular: ' . $e->getMessage());
                
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['particular', 'partylist', 'district', 'municipality', 'province', 'region'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("Validation error: The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
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
            if($row['municipality'] !== 'Not Applicable') {
                $municipality = Municipality::where('name', $row['municipality'])
                    ->where('province_id', $province->id)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$municipality) {
                    throw new \Exception("The Municipality named '{$row['municipality']}' is not existing.");
                }

                // dd($municipality);

                $districtQuery->where('municipality_id', $municipality->id);
            }
            else {
                $districtQuery->where('municipality_id', null);
            }
        }
        

        $district = $districtQuery->first();

        if (!$province) {
            throw new \Exception("The District named '{$row['district']}' under Province named '{$row['province']}' is not existing.");
        }

        return $district;
    }
}