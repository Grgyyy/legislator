<?php

namespace App\Filament\Resources\TviTypeResource\Pages;

use App\Filament\Resources\TviTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTviTypes extends ListRecords
{
    protected static string $resource = TviTypeResource::class;

    protected static ?string $title = 'Institution Types';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New')
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Institution Types',
            'List'
        ];
    }
}
