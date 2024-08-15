<?php

namespace App\Filament\Resources\InstitutionClassResource\Pages;

use App\Filament\Resources\InstitutionClassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstitutionClass extends EditRecord
{
    protected static string $resource = InstitutionClassResource::class;

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
