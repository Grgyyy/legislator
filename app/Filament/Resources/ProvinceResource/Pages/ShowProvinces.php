<?php
namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Models\Region;
use App\Filament\Resources\ProvinceResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowProvinces extends ListRecords
{
    protected static string $resource = ProvinceResource::class;

    public function getBreadcrumbs(): array
    {
        $regionId = $this->getRegionId();

        $region = Region::find($regionId);

        return [
            route('filament.admin.resources.regions.index', ['record' => $region->id]) => $region ? $region->name : 'Regions',
            'Provinces',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        $regionId = $this->getRegionId();

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.provinces.create', ['region_id' => $regionId])),
        ];
    }

    protected function getRegionId(): ?int
    {
        return (int) request()->route('record');
    }
}