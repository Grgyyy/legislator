<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Filament\Resources\DistrictResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDistrict extends CreateRecord
{
    protected static string $resource = DistrictResource::class;
    
    protected function getRedirectUrl(): string 
    {
        $municipality_id = $this->record->municipality_id;
        
        if ($municipality_id) {
            return route('filament.admin.resources.municipalities.showDistricts', ['record' => $municipality_id]);
        }

        return $this->getResource()::getUrl('index');
    }
}
