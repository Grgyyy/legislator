<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Filament\Resources\ProvinceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Request;

class CreateProvince extends CreateRecord
{
    protected static string $resource = ProvinceResource::class;

    protected function getRedirectUrl(): string
    {
        $regionId = Request::input('region_id');
        
        if ($regionId) {
            return route('filament.resources.provinces.provinces-under-region', ['record' => $regionId]);
        }

        return $this->getResource()::getUrl('index');
    }
}
