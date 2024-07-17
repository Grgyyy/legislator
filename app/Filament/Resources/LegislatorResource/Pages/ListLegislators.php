<?php

namespace App\Filament\Resources\LegislatorResource\Pages;

use App\Filament\Resources\LegislatorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLegislators extends ListRecords
{
    protected static string $resource = LegislatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
