<?php

namespace App\Filament\Resources\AbddResource\Pages;

use App\Filament\Resources\AbddResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAbdd extends CreateRecord
{
    protected static string $resource = AbddResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            '/abdds' => 'ABDD Sectors',
            'Create'
        ];
    }

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
