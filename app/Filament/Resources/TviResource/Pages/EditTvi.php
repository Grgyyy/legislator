<?php

namespace App\Filament\Resources\TviResource\Pages;

use App\Filament\Resources\TviResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTvi extends EditRecord
{
    protected static string $resource = TviResource::class;

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
        return $this->getResource()::getUrl('index');
    }
}
