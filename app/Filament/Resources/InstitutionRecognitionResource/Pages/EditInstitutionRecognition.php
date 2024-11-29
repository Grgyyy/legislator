<?php

namespace App\Filament\Resources\InstitutionRecognitionResource\Pages;

use App\Filament\Resources\InstitutionRecognitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstitutionRecognition extends EditRecord
{
    protected static string $resource = InstitutionRecognitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
