<?php

namespace App\Filament\Resources\AttributionTargetResource\Pages;

use App\Filament\Resources\AttributionTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttributionTargets extends ListRecords
{
    protected static string $resource = AttributionTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.attribution-targets.index') => 'Attribution Targets',
            'List'
        ];
    }

    protected static ?string $title = 'Attribution Targets';
}
