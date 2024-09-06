<?php

namespace App\Filament\Resources\AbddResource\Pages;

use App\Filament\Resources\AbddResource;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAbdd extends EditRecord
{
    protected static string $resource = AbddResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            '/abdds' => 'ABDD Sectors',
            'Edit'
        ];
    }

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
