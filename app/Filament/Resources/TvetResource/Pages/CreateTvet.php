<?php

namespace App\Filament\Resources\TvetResource\Pages;

use App\Filament\Resources\TvetResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTvet extends CreateRecord
{
    protected static string $resource = TvetResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            '/tvets/create' => 'TVET Sectors',
            'Create'
        ];
    }

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
