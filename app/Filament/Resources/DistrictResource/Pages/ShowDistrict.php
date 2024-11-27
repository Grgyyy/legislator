<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Models\District;
use App\Filament\Resources\DistrictResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowDistrict extends ListRecords
{
    protected static string $resource = DistrictResource::class;

    // public function getBreadcrumbs(): array
    // {
    //     $districtId = $this->getDistrictId();
    //     $district = District::find($districtId);

    //     $province = $district->province;
    //     $region = $district->province->region;

    //     return [
    //         route('filament.admin.resources.regions.index', ['record' => $region->id]) => $district ? $region->name : 'Regions',
    //         route('filament.admin.resources.provinces.showProvince', ['record' => $province->id]) => $district ? $province->name : 'Provinces',
    //         'Districts',
    //         'List'
    //     ];
    // }

    protected function getHeaderActions(): array
    {
        $districtId = $this->getDistrictId();

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.districts.create', ['district_id' => $districtId])),
        ];
    }

    protected function getDistrictId(): ?int
    {
        return (int) request()->route('record');
    }
}
