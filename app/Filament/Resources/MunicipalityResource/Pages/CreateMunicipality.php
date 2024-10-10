<?php

namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Models\Municipality;
use App\Filament\Resources\MunicipalityResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateMunicipality extends CreateRecord
{
    protected static string $resource = MunicipalityResource::class;

    protected function getRedirectUrl(): string
    {
        $provinceId = $this->record->province_id;

        if ($provinceId) {
            return route('filament.admin.resources.provinces.showMunicipalities', ['record' => $provinceId]);
        }

        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Municipality
    {
        $this->validateUniqueMunicipality($data['name'], $data['province_id']);

        $municipality = DB::transaction(fn() => Municipality::create([
            'name' => $data['name'],
            'province_id' => $data['province_id'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Municipality has been created successfully.');

        return $municipality;
    }

    protected function validateUniqueMunicipality($name, $provinceId)
    {
        $municipality = Municipality::withTrashed()
            ->where('name', $name)
            ->where('province_id', $provinceId)
            ->first();

        if ($municipality) {
            $message = $municipality->deleted_at 
                ? 'This municipality exists in the province but has been deleted; it must be restored before reuse.' 
                : 'A municipality with this name already exists in the specified province.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}