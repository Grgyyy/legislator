<?php

namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Models\Municipality;
use App\Filament\Resources\MunicipalityResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowMunicipalities extends ListRecords
{
    protected static string $resource = MunicipalityResource::class;

    // public function getBreadcrumbs(): array
    // {
    //     $municipalityId = $this->getMunicipalityId();
    //     $municipality = Municipality::find($municipalityId);

    //     $district = $municipality->district;
    //     $province = $municipality->district->province;
    //     $region = $municipality->district->province->region;


    //     return[
    //         route('filament.admin.resources.regions.index', ['record' => $region->id]) => $district ? $region->name : 'Regions',
    //         route('filament.admin.resources.provinces.showProvince', ['record' => $province->id]) => $district ? $province->name : 'Provinces',
    //         route('filament.admin.resources.provinces.showProvince', ['record' => $district->id]) => $district ? $district->name : 'District',
    //         'Municipalities',
    //         'List',
    //     ];
    // }

    protected function getHeaderActions(): array
    {
        $districtId = $this->getMunicipalityId();

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.municipalities.create', ['district_id' => $districtId])),
        ];
    }

    protected function getMunicipalityId(): ?int
    {
        return (int) request()->route('record');
    }
}