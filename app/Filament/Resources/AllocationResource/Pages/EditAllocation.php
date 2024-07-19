<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Filament\Resources\AllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAllocation extends EditRecord
{
    protected static string $resource = AllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(), 
            Actions\RestoreAction::make(), 
        ];
    }

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
