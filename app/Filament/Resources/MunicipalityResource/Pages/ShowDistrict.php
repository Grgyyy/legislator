<?php

namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Models\Municipality;
use App\Filament\Resources\DistrictResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowDistrict extends ListRecords
{
    protected static string $resource = DistrictResource::class;

    public function getBreadcrumbs(): array
    {
        $municipalityId = $this->getMunicipalityId();
        $municipality = Municipality::find($municipalityId);

        $province = $municipality->province;
        $region = $municipality->province->region;

        return [
            route('filament.admin.resources.regions.show_provinces', ['record' => $region->id]) => $municipality ? $region->name : 'Regions',
            route('filament.admin.resources.provinces.showMunicipalities', ['record' => $province->id]) => $municipality ? $province->name : 'Provinces',
            route('filament.admin.resources.municipalities.showDistricts', ['record' => $municipalityId]) => $municipality ? $municipality->name : 'Municipalities',
            'Districts',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        $municipalityId = $this->getMunicipalityId();

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.districts.create', ['municipality_id' => $municipalityId])),
        ];
    }

    protected function getMunicipalityId(): ?int
    {
        return (int) request()->route('record');
    }
}