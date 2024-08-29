<?php

namespace App\Filament\Resources\TvetResource\Pages;

use App\Filament\Resources\TvetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTvets extends ListRecords
{
    protected static string $resource = TvetResource::class;

    protected static ?string $title = 'TVET Sectors';

    public function getBreadcrumbs(): array
    {
        return [
            '/tvets' => 'TVET Sectors',
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
