<?php

namespace App\Filament\Resources\LegislatorResource\Pages;

use App\Filament\Resources\LegislatorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLegislator extends EditRecord
{
    protected static string $resource = LegislatorResource::class;

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
