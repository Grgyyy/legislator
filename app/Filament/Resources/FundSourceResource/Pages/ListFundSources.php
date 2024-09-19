<?php

namespace App\Filament\Resources\FundSourceResource\Pages;

use App\Filament\Resources\FundSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFundSources extends ListRecords
{
    protected static string $resource = FundSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
