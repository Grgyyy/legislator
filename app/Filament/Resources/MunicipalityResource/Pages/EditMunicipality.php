<?php

namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Filament\Resources\MunicipalityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMunicipality extends EditRecord
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
