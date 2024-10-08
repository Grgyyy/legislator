<?php

namespace App\Filament\Resources\NonCompliantTargetResource\Pages;

use App\Filament\Resources\NonCompliantTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNonCompliantTargets extends ListRecords
{
    protected static string $resource = NonCompliantTargetResource::class;

    protected static ?string $title = 'Non-Compliant Targets';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.non-compliant-targets.index') => 'Non-Compliant Targets',
            'List'
        ];
    }
}
