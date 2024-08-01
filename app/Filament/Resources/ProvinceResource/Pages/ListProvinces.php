<?php
namespace App\Filament\Resources\ProvinceResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProvinceResource;

class ListProvinces extends ListRecords
{
    protected static string $resource = ProvinceResource::class;

    protected function getTableQuery(): ?Builder
    {
        // Assuming you have a `region_id` column in your `provinces` table
        return parent::getTableQuery()->where('region_id', request('record'));
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Province'),
        ];
    }
}

