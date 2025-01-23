<?php

namespace App\Filament\Resources\LegislativeTargetsResource\Pages;

use App\Filament\Resources\LegislativeTargetsResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateLegislativeTargets extends CreateRecord
{
    protected static string $resource = LegislativeTargetsResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }
}
