<?php

namespace App\Filament\Resources\LegislativeTargetsResource\Pages;

use App\Filament\Resources\LegislativeTargetsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLegislativeTargets extends EditRecord
{
    protected static string $resource = LegislativeTargetsResource::class;

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
            $this->getCancelFormAction(),
        ];
    }
}
