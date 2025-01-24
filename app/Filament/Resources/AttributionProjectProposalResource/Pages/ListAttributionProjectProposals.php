<?php

namespace App\Filament\Resources\AttributionProjectProposalResource\Pages;

use App\Filament\Resources\AttributionProjectProposalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttributionProjectProposals extends ListRecords
{
    protected static string $resource = AttributionProjectProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected static ?string $title = 'Attribution Project Proposals';

    public function getBreadcrumbs(): array
    {
        return [
            '/attribution-project-proposals' => 'Attribution Project Proposals',
            'List'
        ];
    }
}
