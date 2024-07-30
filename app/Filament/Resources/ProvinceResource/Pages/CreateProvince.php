<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Filament\Resources\ProvinceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProvince extends CreateRecord
{
    protected static string $resource = ProvinceResource::class;

    protected function getRedirectUrl(): string
    {
        // Get the region ID from the newly created record (or form input)
        $regionId = $this->record->region_id ?? request('region_id');

        // Generate the URL for the view_provinces page with the region ID
        return route('filament.resources.provinces.view_provinces', ['record' => $regionId]);
    }
}
