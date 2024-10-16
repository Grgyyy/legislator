<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Models\Province;
use App\Filament\Resources\MunicipalityResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowMunicipalities extends ListRecords
{
    protected static string $resource = MunicipalityResource::class;

    public function getBreadcrumbs(): array
{
    $provinceId = $this->getProvinceId();
    $province = Province::find($provinceId);

    // Check if the province exists
    if (!$province) {
        return [
            route('filament.admin.resources.regions.index') => 'Regions',
            'Provinces' => 'Provinces',
            'Municipalities' => 'Municipalities',
            'List' => 'List',
        ];
    }

    $region = $province->region;

    return [
        route('filament.admin.resources.regions.show_provinces', ['record' => $region->id]) => $region ? $region->name : 'Regions',
        route('filament.admin.resources.provinces.showMunicipalities', ['record' => $provinceId]) => $province->name,
        'Municipalities',
        'List',
    ];
}


    protected function getHeaderActions(): array
    {
        $provinceId = $this->getProvinceId();

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.municipalities.create', ['province_id' => $provinceId])),
        ];
    }

    protected function getProvinceId(): ?int
    {
        return (int) request()->route('record');
    }
}