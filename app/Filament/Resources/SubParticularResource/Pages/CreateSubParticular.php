<?php

namespace App\Filament\Resources\SubParticularResource\Pages;

use App\Filament\Resources\SubParticularResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSubParticular extends CreateRecord
{
    protected static string $resource = SubParticularResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
