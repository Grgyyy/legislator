<?php

namespace App\Filament\Resources\AdbbResource\Pages;

use App\Filament\Resources\AdbbResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdbbs extends ListRecords
{
    protected static string $resource = AdbbResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
