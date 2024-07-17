<?php

namespace App\Filament\Resources\LegislatorResource\Pages;

use App\Filament\Resources\LegislatorResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLegislator extends CreateRecord
{
    protected static string $resource = LegislatorResource::class;

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
