<?php

namespace App\Filament\Resources\TviClassResource\Pages;

use App\Filament\Resources\TviClassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTviClass extends EditRecord
{
    protected static string $resource = TviClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
