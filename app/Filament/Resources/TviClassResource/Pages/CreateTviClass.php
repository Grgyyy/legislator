<?php

namespace App\Filament\Resources\TviClassResource\Pages;

use App\Filament\Resources\TviClassResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTviClass extends CreateRecord
{
    protected static string $resource = TviClassResource::class;

    protected static ?string $title = 'Create Institution Class';

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Institution Classes',
            'Create'
        ];
    }
}
