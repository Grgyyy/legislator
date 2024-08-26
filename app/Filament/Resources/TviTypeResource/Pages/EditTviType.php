<?php

namespace App\Filament\Resources\TviTypeResource\Pages;

use App\Filament\Resources\TviTypeResource;
use Filament\Resources\Pages\EditRecord;

class EditTviType extends EditRecord
{
    protected static string $resource = TviTypeResource::class;

    protected static ?string $title = 'Edit Institution Type';

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Institution Types',
            'Edit'
        ];
    }
}
