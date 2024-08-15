<?php

namespace App\Filament\Resources\TviResource\Pages;

use App\Filament\Resources\TviResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTvi extends CreateRecord
{
    protected static string $resource = TviResource::class;

    protected static ?string $title = 'Create Provider';

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
