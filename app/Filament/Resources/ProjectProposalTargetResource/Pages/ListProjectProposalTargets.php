<?php

namespace App\Filament\Resources\ProjectProposalTargetResource\Pages;

use App\Filament\Resources\ProjectProposalTargetResource;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjectProposalTargets extends ListRecords
{
    protected static string $resource = ProjectProposalTargetResource::class;

    protected static ?string $title = 'Project Proposals';

    public function getBreadcrumbs(): array
    {
        return [
            '/project-proposal-targets' => 'Project Proposals',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),
        ];
    }
}
