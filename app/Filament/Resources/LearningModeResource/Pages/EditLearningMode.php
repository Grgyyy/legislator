<?php

namespace App\Filament\Resources\LearningModeResource\Pages;

use App\Filament\Resources\LearningModeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLearningMode extends EditRecord
{
    protected static string $resource = LearningModeResource::class;

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
