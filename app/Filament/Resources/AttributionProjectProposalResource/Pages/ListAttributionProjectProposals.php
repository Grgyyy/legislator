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
}
