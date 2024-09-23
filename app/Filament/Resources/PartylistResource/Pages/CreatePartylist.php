<?php

namespace App\Filament\Resources\PartylistResource\Pages;

use App\Filament\Resources\PartylistResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePartylist extends CreateRecord
{
    protected static string $resource = PartylistResource::class;

    protected static ?string $title = 'Create Party-List';

    public function getBreadcrumbs(): array
    {
        return [
            '/partylists' => 'Party-List',
            'Create'
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
