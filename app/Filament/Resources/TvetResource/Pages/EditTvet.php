<?php

namespace App\Filament\Resources\TvetResource\Pages;

use App\Filament\Resources\TvetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTvet extends EditRecord
{
    protected static string $resource = TvetResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            '/tvets' => 'TVET Sectors',
            'Edit'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
