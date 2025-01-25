<?php

namespace App\Filament\Resources\LearningModeResource\Pages;


use App\Filament\Resources\LearningModeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ShowLearningMode extends ListRecords
{
    protected static string $resource = LearningModeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
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

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }
}
