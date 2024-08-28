<?php

namespace App\Filament\Resources\InstitutionClassResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\InstitutionClassResource;

class ListInstitutionClasses extends ListRecords
{
    protected static string $resource = InstitutionClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),
        ];
    }
}
