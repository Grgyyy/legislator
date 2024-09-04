<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Filament\Resources\ProvinceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateProvince extends CreateRecord
{
    protected static string $resource = ProvinceResource::class;

    protected function getRedirectUrl(): string
    {
        $regionId = $this->record->region_id;

        if ($regionId) {
            return route('filament.admin.resources.regions.show_provinces', ['record' => $regionId]);
        }

        return $this->getResource()::getUrl('index');
    }


}
