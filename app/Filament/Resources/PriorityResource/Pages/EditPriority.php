<?php

namespace App\Filament\Resources\PriorityResource\Pages;

use App\Filament\Resources\PriorityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPriority extends EditRecord
{
    protected static string $resource = PriorityResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            'Top Ten Priority Sectors',
            'Edit'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
