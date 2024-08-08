<?php

namespace App\Filament\Resources\TviTypeResource\Pages;

use App\Filament\Resources\TviTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTviType extends EditRecord
{
    protected static string $resource = TviTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
