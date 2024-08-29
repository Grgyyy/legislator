<?php

namespace App\Filament\Resources\PriorityResource\Pages;

use App\Filament\Resources\PriorityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPriorities extends ListRecords
{
    protected static string $resource = PriorityResource::class;

    protected static ?string $title = 'Top Ten Priority Sectors';

    public function getBreadcrumbs(): array
    {
        return [
            '/priorities' => 'Top Ten Priority Sectors',
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
