<?php

namespace App\Filament\Resources\SubParticularResource\Pages;

use App\Filament\Resources\SubParticularResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubParticulars extends ListRecords
{
    protected static string $resource = SubParticularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
