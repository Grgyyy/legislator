<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Filament\Resources\MunicipalityResource;
use App\Models\Province;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowMunicipalities extends ListRecords
{
    protected static string $resource = MunicipalityResource::class;

    public function getBreadcrumbs(): array
    {
        $province = $this->getProvince();

        $region = $province->region;

        return [
            route('filament.admin.resources.regions.show_provinces', ['record' => $region->id]) => $region->name,
            route('filament.admin.resources.provinces.showMunicipalities', ['record' => $province->id]) => $province->name,
            'Municipalities',
            'List',
        ];
    }

    protected function getHeaderActions(): array
    {
        $province = $this->getProvince();

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.municipalities.create', ['province_id' => $province->id])),
        ];
    }

    protected function getProvince(): ?Province
    {
        $provinceId = (int) request()->route('record');

        return Province::find($provinceId);
    }
}