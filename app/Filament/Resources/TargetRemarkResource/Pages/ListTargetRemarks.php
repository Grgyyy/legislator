<?php

namespace App\Filament\Resources\TargetRemarkResource\Pages;

use App\Filament\Resources\TargetRemarkResource;
use Filament\Actions;
use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTargetRemarks extends ListRecords
{
    protected static string $resource = TargetRemarkResource::class;

    protected ?string $heading = 'Non-Compliant Target Remark';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.target-remarks.index') => 'Non-Compliant Target Remark',
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
