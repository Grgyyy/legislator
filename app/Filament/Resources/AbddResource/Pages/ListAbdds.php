<?php

namespace App\Filament\Resources\AbddResource\Pages;

use App\Filament\Resources\AbddResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAbdds extends ListRecords
{
    protected static string $resource = AbddResource::class;

    protected static ?string $title = 'ABDD Sectors';

    public function getBreadcrumbs(): array
    {
        return [
            '/abdds' => 'ABDD Sectors',
            'List'
        ];
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
