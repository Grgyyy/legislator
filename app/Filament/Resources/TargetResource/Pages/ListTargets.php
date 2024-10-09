<?php

namespace App\Filament\Resources\TargetResource\Pages;

use App\Filament\Resources\TargetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTargets extends ListRecords
{
    protected static string $resource = TargetResource::class;

    protected ?string $heading = 'Pending Targets';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.targets.index') => 'Pending Targets',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New')
        ];
    }
}
