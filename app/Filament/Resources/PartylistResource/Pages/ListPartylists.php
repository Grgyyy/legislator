<?php

namespace App\Filament\Resources\PartylistResource\Pages;

use App\Filament\Resources\PartylistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartylists extends ListRecords
{
    protected static string $resource = PartylistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
