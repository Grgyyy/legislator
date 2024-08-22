<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Filament\Resources\MunicipalityResource;
use App\Models\Province;
use App\Models\Region;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowMunicipalities extends ListRecords
{
    protected static string $resource = MunicipalityResource::class;

    protected ?string $heading = 'Municipalities';

    public function getBreadcrumbs(): array
    {
        $regionId = $this->getRegionId();
        $provinceId = $this->getProvinceId();
    
        $region = Region::find($regionId);
        $province = Province::find($provinceId);

        $region_id = $province->region->id;
        
        return [

            route('filament.admin.resources.regions.show_provinces', ['record' => $region_id]) => $province ? $province->region->name : 'Regions',
            route('filament.admin.resources.provinces.showMunicipalities', ['record' => $provinceId]) => $province ? $province->name : 'Provinces',
            'Municipalities',
            'List'
        ];
    }

    // route('filament.admin.resources.provinces.create', ['region_id' => $regionId])

    protected function getHeaderActions(): array
    {
        $provinceId = $this->getProvinceId();

        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New')
                ->url(route('filament.admin.resources.municipalities.create', ['province_id' => $provinceId]))
        ];
    }

    protected function getProvinceId(): ?int
    {
        return (int) request()->route('record');
    }

    protected function getRegionId(): ?int
    {
        return (int) request()->route('record');
    }
}
