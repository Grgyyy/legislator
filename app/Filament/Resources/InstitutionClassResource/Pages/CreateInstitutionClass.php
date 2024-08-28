<?php

namespace App\Filament\Resources\InstitutionClassResource\Pages;

use App\Filament\Resources\InstitutionClassResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstitutionClass extends CreateRecord
{
    protected static string $resource = InstitutionClassResource::class;
    
    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
