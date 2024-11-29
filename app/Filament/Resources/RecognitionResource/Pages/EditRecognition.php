<?php

namespace App\Filament\Resources\RecognitionResource\Pages;

use App\Filament\Resources\RecognitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecognition extends EditRecord
{
    protected static string $resource = RecognitionResource::class;

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
