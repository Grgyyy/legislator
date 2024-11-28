<?php

namespace App\Filament\Clusters\Sectors\Resources\ProvinceAbddResource\Pages;

use App\Filament\Clusters\Sectors\Resources\ProvinceAbddResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProvinceAbdds extends ListRecords
{
    protected static string $resource = ProvinceAbddResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
