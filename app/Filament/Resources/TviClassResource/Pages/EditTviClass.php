<?php

namespace App\Filament\Resources\TviClassResource\Pages;

use App\Filament\Resources\TviClassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTviClass extends EditRecord
{
    protected static string $resource = TviClassResource::class;

    protected static ?string $title = 'Edit Institution Class';

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
