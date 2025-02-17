<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\District;
use App\Models\Legislator;
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

class LegislatorImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $subParticularName = $this->getSubParticularName(Helper::capitalizeWords($row['particular']));
                $partylistName = $this->getPartylist(Helper::capitalizeWords($row['partylist']));
                $districtName = $this->getDistrict($row);
                $particularName = $this->getParticular($subParticularName->id, $partylistName->id, $districtName->id);

                $legislator = Legislator::firstOrCreate(['name' => Helper::capitalizeWords($row['legislator'])]);

                if (!$legislator->particular()->where('particular_id', $particularName->id)->exists()) {
                    $legislator->particular()->attach($particularName->id);
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

    protected function getSubParticularName(string $subParticularName)
    {
        $subParticular = SubParticular::where('name', $subParticularName)
            ->whereNull('deleted_at')
            ->first();

        if (!$subParticular) {
            throw new \Exception("The Particular named '{$subParticularName}' is not existing.");
        }

        return $subParticular;
    }

    protected function getPartylist(string $partylistName)
    {
        $partylist = Partylist::where('name', $partylistName)
            ->whereNull('deleted_at')
            ->first();

        if (!$partylist) {
            throw new \Exception("The Partylist named '{$partylistName}' is not existing.");
        }

        return $partylist;
    }

    protected function getDistrict(array $row)
    {
        $region = Region::where('name', Helper::capitalizeWords($row['region']))
            ->whereNull('deleted_at')
            ->first();

        if (!$region) {
            throw new \Exception("The Region named '{$row['region']}' is not existing.");
        }

        $province = Province::where('name', Helper::capitalizeWords($row['province']))
            ->where('region_id', $region->id)
            ->whereNull('deleted_at')
            ->first();

        if (!$province) {
            throw new \Exception("The Province named '{$row['province']}' is not existing.");
        }

        $districtQuery = District::where('name', Helper::capitalizeWords($row['district']))
            ->where('province_id', $province->id)
            ->whereNull('deleted_at');

        if ($row['particular'] === 'District') {
            if ($row['region'] === 'NCR') {
                $municipality = Municipality::where('name', Helper::capitalizeWords($row['municipality']))
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

        if (!$district) {
            throw new \Exception("The District named '{$row['district']}' under Province named '{$row['province']}' is not existing.");
        }

        return $district;
    }

    protected function getParticular(int $subParticularId, int $partylistId, int $districtId)
    {
        $subParticular = SubParticular::find($subParticularId);

        $particular = Particular::where('sub_particular_id', $subParticularId)
            ->where('partylist_id', $partylistId)
            ->where('district_id', $districtId)
            ->whereNull('deleted_at')
            ->first();

        if (!$particular) {
            throw new \Exception("The Particular named '{$subParticular->name}' is not existing.");
        }

        return $particular;
    }
}
