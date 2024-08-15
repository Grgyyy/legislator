<?php

namespace App\Filament\Resources\TviTypeResource\Pages;

use App\Filament\Resources\TviTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTviTypes extends ListRecords
{
    protected static string $resource = TviTypeResource::class;

    protected static ?string $title = 'Institution Type';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Institution Type'),
        ];
    }
}
