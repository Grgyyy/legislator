<?php

namespace App\Filament\Resources\QualificationTitleResource\Pages;

use App\Filament\Resources\QualificationTitleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQualificationTitle extends CreateRecord
{
    protected static string $resource = QualificationTitleResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
