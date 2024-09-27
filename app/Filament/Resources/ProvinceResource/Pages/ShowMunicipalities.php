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
        $provinceId = $this->getProvinceId();
        $province = Province::find($provinceId);

        $region = $province->region;

        return [
            route('filament.admin.resources.regions.show_provinces', ['record' => $region->id]) => $province ? $region->name : 'Regions',
            route('filament.admin.resources.provinces.showMunicipalities', ['record' => $province->id]) => $province ? $province->name : 'Provinces',
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