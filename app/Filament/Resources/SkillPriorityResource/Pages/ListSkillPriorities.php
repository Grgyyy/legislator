<?php

namespace App\Filament\Resources\SkillPriorityResource\Pages;

use App\Filament\Resources\SkillPriorityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSkillPriorities extends ListRecords
{
    protected static string $resource = SkillPriorityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
