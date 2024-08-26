<?php

namespace App\Filament\Resources\TviResource\Pages;

use App\Filament\Resources\TviResource;
use Filament\Resources\Pages\EditRecord;

class EditTvi extends EditRecord
{
    protected static string $resource = TviResource::class;

    protected static ?string $title = 'Edit Institution';

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Institutions',
            'Edit'
        ];
    }
}
