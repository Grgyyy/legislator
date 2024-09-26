<?php
namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\ProvinceResource;
use App\Models\Region;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ShowProvinces extends ListRecords
{
    protected static string $resource = ProvinceResource::class;

    public function getBreadcrumbs(): array
    {
        $regionId = $this->getRegionId();

        $region = Region::find($regionId);

        return [
            route('filament.admin.resources.regions.show_provinces', ['record' => $regionId]) => $region ? $region->name : 'Regions',
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
                ->url(route('filament.admin.resources.provinces.create', ['region_id' => $regionId]))
        ];
    }

    protected function getRegionId(): ?int
    {
        return (int) request()->route('record');
    }
}
