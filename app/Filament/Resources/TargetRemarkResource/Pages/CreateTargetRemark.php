<?php

namespace App\Filament\Resources\TargetRemarkResource\Pages;

use App\Filament\Resources\TargetRemarkResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTargetRemark extends CreateRecord
{
    protected static string $resource = TargetRemarkResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
