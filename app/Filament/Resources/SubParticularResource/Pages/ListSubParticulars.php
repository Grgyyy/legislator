<?php

namespace App\Filament\Resources\SubParticularResource\Pages;

use App\Filament\Resources\SubParticularResource;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubParticulars extends ListRecords
{
    protected static string $resource = SubParticularResource::class;

    protected static ?string $title = 'Particular Types';

    public function getBreadcrumbs(): array
    {
        return [
            '/sub-particulars' => 'Particular Types',
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
