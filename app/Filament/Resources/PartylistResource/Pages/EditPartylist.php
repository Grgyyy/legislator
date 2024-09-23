<?php

namespace App\Filament\Resources\PartylistResource\Pages;

use App\Filament\Resources\PartylistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartylist extends EditRecord
{
    protected static string $resource = PartylistResource::class;

    protected static ?string $title = 'Edit Party-List';

    public function getBreadcrumbs(): array
    {
        return [
            '/partylists' => 'Party-List',
            'Edit'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
