<?php

namespace App\Filament\Resources\NonCompliantRemarkResource\Pages;

use App\Filament\Resources\NonCompliantRemarkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNonCompliantRemarks extends ListRecords
{
    protected static string $resource = NonCompliantRemarkResource::class;
    protected static ?string $title = 'Non-Compliant Targets';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.non-compliant-remarks.index') => 'Non-Compliant Targets',
            'List'
        ];
    }
    
    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
