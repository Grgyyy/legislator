<?php

namespace App\Filament\Resources\QualificationTitleResource\Pages;

use App\Filament\Resources\QualificationTitleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQualificationTitle extends EditRecord
{
    protected static string $resource = QualificationTitleResource::class;

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
