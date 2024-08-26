<?php

namespace App\Filament\Resources\TviResource\Pages;

use App\Filament\Resources\TviResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTvi extends CreateRecord
{
    protected static string $resource = TviResource::class;

    protected static ?string $title = 'Create Institution';

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Institutions',
            'Create'
        ];
    }
}
