<?php

namespace App\Filament\Resources\TviResource\Pages;

use App\Filament\Resources\TviResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTvis extends ListRecords
{
    protected static string $resource = TviResource::class;

    protected static ?string $title = 'Institution';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New')
        ];
    }
}
