<?php

namespace App\Filament\Resources\PartylistResource\Pages;

use App\Filament\Resources\PartylistResource;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPartylists extends ListRecords
{
    protected static string $resource = PartylistResource::class;

    protected static ?string $title = 'Party-List';

    public function getBreadcrumbs(): array
    {
        return [
            '/partylists' => 'Party-List',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),
        ];
    }
}
