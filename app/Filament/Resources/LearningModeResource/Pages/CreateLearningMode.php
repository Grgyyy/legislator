<?php

namespace App\Filament\Resources\LearningModeResource\Pages;

use App\Filament\Resources\LearningModeResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateLearningMode extends CreateRecord
{
    protected static string $resource = LearningModeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }
}
