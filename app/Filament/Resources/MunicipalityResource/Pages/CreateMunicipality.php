<?php

namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Filament\Resources\MunicipalityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMunicipality extends CreateRecord
{
    protected static string $resource = MunicipalityResource::class;

    protected function getRedirectUrl(): string
    {
        $province_id = $this->record->province_id;

        if ($province_id) {
            return route('filament.admin.resources.provinces.showMunicipalities', ['record' => $province_id]);
        }

        return $this->getResource()::getUrl('index');
    }
}
