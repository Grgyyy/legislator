<?php

namespace App\Filament\Resources\TviResource\Pages;

use App\Filament\Resources\TviResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTvis extends ListRecords
{
    protected static string $resource = TviResource::class;

    protected static ?string $title = 'Provider';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New TVI'),
        ];
    }
}
