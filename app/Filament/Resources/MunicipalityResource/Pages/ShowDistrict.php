<?php

namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Filament\Resources\DistrictResource;
use App\Filament\Resources\MunicipalityResource;
use App\Models\District;
use App\Models\Municipality;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowDistrict extends ListRecords
{
    protected static string $resource = DistrictResource::class;

    public function getBreadcrumbs(): array
    {
        $municipalityId = $this->getMunicipalityId();
        
        $municipality = Municipality::find($municipalityId);

        $province_id = $municipality->province->id;
        $region_id = $municipality->province->region->id;

        return [
            route('filament.admin.resources.regions.show_provinces', ['record' => $region_id]) => $municipality->province->region->name ?? 'Regions',
            route('filament.admin.resources.provinces.showMunicipalities', ['record' => $province_id]) => $municipality->province->name ?? 'Provinces',
            route('filament.admin.resources.municipalities.showDistricts', ['record' => $municipalityId]) => $municipality->name ?? 'Municipalities',
            'Districts',
            'List'
        ];
    }


    protected function getHeaderActions(): array
    {
        $municipalityId = $this->getMunicipalityId();

        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New')
                ->url(route('filament.admin.resources.districts.create', ['municipality_id' => $municipalityId]))
        ];
    }

    protected function getMunicipalityId(): ?int
    {
        return (int) request()->route('record');
    }

    protected function getRegionId(): ?int
    {
        return (int) request()->route('record');
    }
}
