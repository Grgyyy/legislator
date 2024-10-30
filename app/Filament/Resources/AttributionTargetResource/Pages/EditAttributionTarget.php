<?php

namespace App\Filament\Resources\AttributionTargetResource\Pages;

use App\Filament\Resources\AttributionTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttributionTarget extends EditRecord
{
    protected static string $resource = AttributionTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
