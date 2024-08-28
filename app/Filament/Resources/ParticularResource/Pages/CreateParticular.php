<?php

namespace App\Filament\Resources\ParticularResource\Pages;

use App\Filament\Resources\ParticularResource;
use Filament\Resources\Pages\CreateRecord;

class CreateParticular extends CreateRecord
{
    protected static string $resource = ParticularResource::class;

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
