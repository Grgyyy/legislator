<?php

namespace App\Filament\Resources\TargetRemarkResource\Pages;

use App\Filament\Resources\TargetRemarkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTargetRemark extends EditRecord
{
    protected static string $resource = TargetRemarkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected ?string $heading = 'Edit  Non-Compliant Target Remark';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.target-remarks.create') => 'Non-Compliant Target Remark',
            'Create'
        ];
    }
}
