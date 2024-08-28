<?php

namespace App\Filament\Resources\AdbbResource\Pages;

use App\Filament\Resources\AdbbResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdbb extends EditRecord
{
    protected static string $resource = AdbbResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
