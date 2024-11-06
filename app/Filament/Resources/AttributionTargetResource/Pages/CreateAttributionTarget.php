<?php

namespace App\Filament\Resources\AttributionTargetResource\Pages;

use App\Filament\Resources\AttributionTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAttributionTarget extends CreateRecord
{
    protected static string $resource = AttributionTargetResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.attribution-targets.create') => 'Create Attribution Target',
            'Create'
        ];
    }

    protected static ?string $title = 'Create Attribution Targets';
}
