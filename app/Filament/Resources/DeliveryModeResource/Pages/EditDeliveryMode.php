<?php

namespace App\Filament\Resources\DeliveryModeResource\Pages;

use App\Filament\Resources\DeliveryModeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryMode extends EditRecord
{
    protected static string $resource = DeliveryModeResource::class;

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
