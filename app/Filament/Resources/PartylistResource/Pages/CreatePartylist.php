<?php

namespace App\Filament\Resources\PartylistResource\Pages;

use App\Filament\Resources\PartylistResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePartylist extends CreateRecord
{
    protected static string $resource = PartylistResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
