<?php

namespace App\Filament\Resources\CompliantTargetsResource\Pages;

use App\Filament\Resources\CompliantTargetsResource;
use App\Models\Target;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompliantTargets extends ListRecords
{
    protected static string $resource = CompliantTargetsResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.compliant-targets.index') => 'Compliant Targets',
            'List'
        ];
    }

    protected static ?string $title = 'Compliant Targets';
}
