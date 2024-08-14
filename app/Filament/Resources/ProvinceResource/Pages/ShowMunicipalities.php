<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Filament\Resources\MunicipalityResource;
use App\Models\Province;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowMunicipalities extends ListRecords
{
    protected static string $resource = MunicipalityResource::class;

    protected ?string $heading = 'Municipalities';

    public function getBreadcrumbs(): array
    {
        $provinceId = $this->getProvinceId();
        
        $province = Province::find($provinceId);
        
        return [
            'regions' => $province ? $province->name : 'Regions',
            'Provinces',
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
}
