<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Models\District;
use App\Filament\Resources\DistrictResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateDistrict extends CreateRecord
{
    protected static string $resource = DistrictResource::class;

    protected function getRedirectUrl(): string
    {
        $municipalityId = $this->record->municipality_id;

        if ($municipalityId) {
            return route('filament.admin.resources.municipalities.showDistricts', ['record' => $municipalityId]);
        }

        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): District
    {


        $this->validateUniqueDistrict($data['name'], $data['code'], $data['province_id']);

        $district = DB::transaction(fn() => District::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'province_id' => $data['province_id'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'District has been created successfully.');

        return $district;
        // dd($data);
    }




    protected function validateUniqueDistrict($name, $provinceId, $code)
    {
        $district = District::withTrashed()
            ->where('name', $name)
            ->where('code', $code)
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
