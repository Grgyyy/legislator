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

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
