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
        
        return [
            'regions' => $province ? $province->region->name : 'Regions',
            'Provinces'=> $province ? $province->name : 'Provinces',
            'Municipalities',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        $provinceId = $this->getProvinceId();

        return [
            CreateAction::make()
                ->label('New Municipality')
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
