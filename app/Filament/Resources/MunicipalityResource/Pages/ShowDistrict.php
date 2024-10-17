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

        if (!$municipality) {
            return [
                route('filament.admin.resources.regions.index') => 'Regions',
                'Provinces' => 'Provinces',
                'Municipalities' => 'Municipalities',
                'Districts' => 'Districts',
                'List' => 'List',
            ];
        }

        $province = $municipality->province;
        $region = $province ? $province->region : null;

        return [
            route('filament.admin.resources.regions.show_provinces', ['record' => $region->id ?? null]) => $region ? $region->name : 'Regions',
            route('filament.admin.resources.provinces.showMunicipalities', ['record' => $province->id ?? null]) => $province ? $province->name : 'Provinces',
            route('filament.admin.resources.municipalities.showDistricts', ['record' => $municipalityId]) => $municipality->name,
            'Districts',
            'List',
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