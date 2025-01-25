<?php

namespace App\Filament\Resources\TargetRemarkResource\Pages;

use App\Filament\Resources\TargetRemarkResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateTargetRemark extends CreateRecord
{
    protected static string $resource = TargetRemarkResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Save & Exit'),
            $this->getCreateAnotherFormAction()
                ->label('Save & Create Another'),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected ?string $heading = 'Create a Non-Compliant Target Remark';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.target-remarks.create') => 'Non-Compliant Target Remark',
            'Create'
        ];
    }
}
