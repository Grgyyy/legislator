<?php

namespace App\Filament\Resources\ParticularResource\Pages;

use App\Filament\Resources\ParticularResource;
use Filament\Resources\Pages\EditRecord;

class EditParticular extends EditRecord
{
    protected static string $resource = ParticularResource::class;

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
