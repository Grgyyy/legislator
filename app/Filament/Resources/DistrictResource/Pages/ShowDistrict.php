<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Models\District;
use App\Filament\Resources\DistrictResource;
use App\Models\Province;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowDistrict extends ListRecords
{
    protected static string $resource = DistrictResource::class;

    public function getBreadcrumbs(): array
    {
        $provinceId = $this->getProvinceId();
        
        // Check if district ID is valid
        if (!$provinceId) {
            return [
                'Province not found',
            ];
        }

        $province = Province::find($provinceId);

        // Check if district is found
        if (!$province) {
            return [
                'Province not found',
            ];
        }

        // $province = $province->province;

        // // Check if province is found
        // if (!$province) {
        //     return [
        //         'Province not found',
        //     ];
        // }

        return [
            route('filament.admin.resources.provinces.showProvince', ['record' => $province->id]) => $province->name,
            'Districts',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        $provinceId = $this->getProvinceId();

        // Ensure districtId is valid before using it
        if (!$provinceId) {
            return [];
        }

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.districts.create', ['district_id' => $provinceId])),
        ];
    }

    protected function getProvinceId(): ?int
    {
        // Safely return the district ID from the route, or null if not found
        return request()->route('record') ? (int) request()->route('record') : null;
    }
}
