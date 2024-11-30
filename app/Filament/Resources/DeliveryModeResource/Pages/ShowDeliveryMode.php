<?php

namespace App\Filament\Resources\DeliveryModeResource\Pages;

use App\Filament\Resources\DeliveryModeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ShowDeliveryMode extends ListRecords
{
    protected static string $resource = DeliveryModeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
