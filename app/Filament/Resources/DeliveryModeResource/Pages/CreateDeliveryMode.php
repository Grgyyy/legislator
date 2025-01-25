<?php

namespace App\Filament\Resources\DeliveryModeResource\Pages;

use App\Filament\Resources\DeliveryModeResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateDeliveryMode extends CreateRecord
{
    protected static string $resource = DeliveryModeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Save & Exit'),
            $this->getCreateAnotherFormAction()
                ->label('Save & Create Another'),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }
}
