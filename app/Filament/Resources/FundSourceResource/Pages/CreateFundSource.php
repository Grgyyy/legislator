<?php

namespace App\Filament\Resources\FundSourceResource\Pages;

use App\Filament\Resources\FundSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFundSource extends CreateRecord
{
    protected static string $resource = FundSourceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}


