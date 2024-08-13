<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Filament\Resources\ProvinceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProvince extends EditRecord
{
    protected static string $resource = ProvinceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(), 
            Actions\RestoreAction::make(), 
        ];
    }

    protected function getRedirectUrl(): string
    {
        $regionId = $this->record->region_id;
        
        if ($regionId) {
            return route('filament.admin.resources.regions.show_provinces', ['record' => $regionId]);
        }

        return $this->getResource()::getUrl('index');
    }
}
