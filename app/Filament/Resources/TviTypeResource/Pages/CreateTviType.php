<?php

namespace App\Filament\Resources\TviTypeResource\Pages;

use App\Filament\Resources\TviTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTviType extends CreateRecord
{
    protected static string $resource = TviTypeResource::class;

    protected static ?string $title = 'Create Institution Type';

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Institution Types',
            'Create'
        ];
    }
}
