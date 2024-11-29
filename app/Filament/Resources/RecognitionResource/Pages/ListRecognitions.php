<?php

namespace App\Filament\Resources\RecognitionResource\Pages;

use App\Filament\Resources\RecognitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecognitions extends ListRecords
{
    protected static string $resource = RecognitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
