<?php

namespace App\Filament\Resources\AdbbResource\Pages;

use App\Filament\Resources\AdbbResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAdbb extends CreateRecord
{
    protected static string $resource = AdbbResource::class;

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
