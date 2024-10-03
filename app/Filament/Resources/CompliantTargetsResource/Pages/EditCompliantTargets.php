<?php

namespace App\Filament\Resources\CompliantTargetsResource\Pages;

use App\Filament\Resources\CompliantTargetsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompliantTargets extends EditRecord
{
    protected static string $resource = CompliantTargetsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
