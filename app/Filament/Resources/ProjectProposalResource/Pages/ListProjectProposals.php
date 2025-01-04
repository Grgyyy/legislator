<?php

namespace App\Filament\Resources\ProjectProposalResource\Pages;

use App\Filament\Resources\ProjectProposalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjectProposals extends ListRecords
{
    protected static string $resource = ProjectProposalResource::class;
    protected ?string $heading = 'Project Proposal Programs';

    public function getBreadcrumbs(): array
    {

        return [
            route('filament.admin.resources.project-proposals.index') => 'Project Proposal Programs',
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
