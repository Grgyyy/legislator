<?php

namespace App\Imports;

use App\Models\Legislator;
use App\Models\Particular;
use App\Models\District;
use App\Models\Municipality;
use App\Models\Partylist;
use App\Models\Province;
use App\Models\Region;
use App\Models\SubParticular;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;

class LegislatorImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $subParticularRecord = $this->getSubParticularName($row['particular']);
                $partylistRecord = $this->getPartylist($row['partylist']);
                $districtRecord = $this->getDistrict($row);
                $particularRecord = $this->getParticular($subParticularRecord->id, $partylistRecord->id, $districtRecord->id);

                $legislator = Legislator::firstOrCreate(['name' => $row['legislator']]);

                if (!$legislator->particular()->where('particular_id', $particularRecord->id)->exists()) {
                    $legislator->particular()->attach($particularRecord->id);
                }

            } catch (Throwable $e) {
                Log::error('Failed to import legislators: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['legislator', 'particular', 'district', 'municipality', 'province', 'region'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
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

    protected function getParticular(int $subParticularId, int $partylistId, int $districtId) {
        $subParticular = SubParticular::find($subParticularId);
        
        $particular = Particular::where('sub_particular_id', $subParticularId)
            ->where('partylist_id', $partylistId)
            ->where('district_id', $districtId)
            ->whereNull('deleted_at')
            ->first();

        if(!$particular) {
            throw new \Exception("The Particular named '{$subParticular->name}' is not existing.");
        }

        return $particular;
    }
}
