<?php

namespace App\Filament\Resources\LearningModeResource\Pages;

use App\Filament\Resources\LearningModeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLearningModes extends ListRecords
{
    protected static string $resource = LearningModeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
