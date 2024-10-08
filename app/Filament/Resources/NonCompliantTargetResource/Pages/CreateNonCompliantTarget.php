<?php

namespace App\Filament\Resources\NonCompliantTargetResource\Pages;

use App\Filament\Resources\NonCompliantTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNonCompliantTarget extends CreateRecord
{
    protected static ?string $title = 'Mark as Non-Compliant Target';

    protected static string $resource = NonCompliantTargetResource::class;

    private const COMPLIANT_STATUS_DESC = 'Non-Compliant';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.non-compliant-targets.index') => 'Targets',
            'Mark as Non-Compliant'
        ];
    }
}
