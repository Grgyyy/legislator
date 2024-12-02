<?php

namespace App\Filament\Clusters\Sectors\Resources\ProvinceAbddResource\Pages;

use App\Filament\Clusters\Sectors\Resources\ProvinceAbddResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProvinceAbdd extends EditRecord
{
    protected static string $resource = ProvinceAbddResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
