<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Models\District;
use App\Models\Province;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\DistrictResource;

class CreateDistrict extends CreateRecord
{
    protected static string $resource = DistrictResource::class;

    protected function getRedirectUrl(): string
    {
        // $municipalityId = $this->record->municipality_id;

        // if ($municipalityId) {
        //     return route('filament.admin.resources.municipalities.showDistricts', ['record' => $municipalityId]);
        // }

        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): District
    {

        if (empty($data['municipality_id']) && isset($data['province_id'])) {
            $province = Province::with('region')->find($data['province_id']);

            if ($province && $province->region->name !== 'NCR') {
                $data['municipality_id'] = null;
            }
        }

        $this->validateUniqueDistrict($data['name'], $data['code'], $data['province_id'], $data['municipality_id']);

        $district = DB::transaction(fn() => District::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'municipality_id' => $data['municipality_id'],
            'province_id' => $data['province_id'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'District has been created successfully.');

        return $district;
    }





    protected function validateUniqueDistrict($name, $provinceId, $code, $municipalityId)
    {
        $district = District::withTrashed()
            ->where('name', $name)
            ->where('code', $code)
            ->where('municipality_id', $municipalityId)
            ->where('province_id', $provinceId)
            ->first();

        if ($district) {
            $message = $district->deleted_at
                ? 'This district exists in the municipality but has been deleted; it must be restored before reuse.'
                : 'A district with this name already exists in the specified municipality.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}
