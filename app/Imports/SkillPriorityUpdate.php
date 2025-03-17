<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\District;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use App\Models\SkillPriority;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SkillPriorityUpdate implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        try {
            $this->validateRow($row);

            return DB::transaction(function () use ($row) {
                $region = $this->getRegion($row['region']);
                $province = $this->getProvince($row['province'], $region->id);
                $municipality = $this->getMunicipality($row['municipality'] ?? null, $province);
                $district = $this->getDistrict($row['district'] ?? null, $province, $municipality);

                $status = Status::where('desc', 'Active')->firstOrFail();

                $skillPriority = SkillPriority::where([
                    'province_id' => $province->id,
                    'district_id' => optional($district)->id,
                    'qualification_title' => $row['lot_name'],
                    'year' => $row['year'],
                ])->first();

                if (!$skillPriority) {
                    throw new \Exception("Skill Priority '{$row['lot_name']}' for the year {$row['year']} does not exist.");
                }

                $previousTotal = $skillPriority->total_slots;
                $previousAvailable = $skillPriority->available_slots;
                $newTotalSlots = (int) $row['target_benificiaries'];
                $newAvailableSlots = max(0, $newTotalSlots - ($previousTotal - $previousAvailable));

                $skillPriority->update([
                    'total_slots' => $newTotalSlots,
                    'available_slots' => $newAvailableSlots,
                    'status_id' => $status->id,
                ]);

                activity()
                ->causedBy(auth()->user())
                ->performedOn($skillPriority)
                ->event('Updated')
                ->withProperties([
                    'province' => $skillPriority->provinces->name,
                    'district' => $skillPriority->district->name ?? null, 
                    'lot_name' => $skillPriority->qualification_title,
                    'qualification_title' => $skillPriority->trainingProgram->implode('title', ', '),
                    'available_slots' => $skillPriority->available_slots,
                    'total_slots' => $skillPriority->total_slots,
                    'year' => $skillPriority->year,
                    'status' => $skillPriority->status->desc,
                ])
                ->log("An Skill Priority for '{$skillPriority->qualification_title}' has been created.");


                return $skillPriority;
            });
        } catch (\Throwable $e) {
            throw new \Exception("An error occurred while processing the import: " . $e->getMessage());
        }
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['lot_name', 'province', 'region', 'target_benificiaries', 'year'];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $row) || empty($row[$field])) {
                throw new \Exception("Missing required field: '{$field}'. No changes were saved.");
            }
        }
    }

    protected function getRegion(string $regionName)
    {
        return Region::where('name', Helper::capitalizeWords(trim($regionName)))
            ->firstOrFail();
    }

    protected function getProvince(string $provinceName, int $regionId)
    {
        return Province::where('name', Helper::capitalizeWords(trim($provinceName)))
            ->where('region_id', $regionId)
            ->firstOrFail();
    }

    protected function getMunicipality(?string $municipalityName, Province $province)
    {
        if (!$municipalityName)
            return null;

        return Municipality::where('name', Helper::capitalizeWords(trim($municipalityName)))
            ->where('province_id', $province->id)
            ->firstOrFail();
    }

    protected function getDistrict(?string $districtName, Province $province, ?Municipality $municipality)
    {
        if (!$districtName)
            return null;

        return District::where('name', Helper::capitalizeWords(trim($districtName)))
            ->where('province_id', $province->id)
            ->when($municipality, fn($query) => $query->where('municipality_id', $municipality->id))
            ->firstOrFail();
    }
}
