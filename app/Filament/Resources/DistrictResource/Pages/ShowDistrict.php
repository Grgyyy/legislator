<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Filament\Resources\DistrictResource;
use App\Models\Province;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;

class ShowDistrict extends ListRecords
{
    protected static string $resource = DistrictResource::class;

    public function getBreadcrumbs(): array
    {

        $provinceId = $this->getProvinceId();

        $province = Province::find($provinceId);
    
        return [
            route('filament.admin.resources.regions.index', ['record' => $province->region->id]) => $province->region ? $province->region->name : 'Regions',
            route('filament.admin.resources.provinces.showProvince', ['record' => $province->id]) => $province ? $province->name : 'Provinces',
            'Districts',
            'List'
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Save & Exit'),
            $this->getCreateAnotherFormAction()
                ->label('Save & Create Another'),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function getHeaderActions(): array
    {
        $provinceId = $this->getProvinceId();

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.districts.create', ['province_id' => $provinceId])),
        ];
    }

    protected function getTableQuery(): Builder|null
    {
        $provinceId = $this->getProvinceId();
        return parent::getTableQuery()->where('province_id', $provinceId);
    }

    protected function getProvinceId(): ?int
    {
        $provinceId = request()->route('record') ?? session('province_id');

        if ($provinceId) {
            session(['province_id' => $provinceId]);
        }

        return (int) $provinceId;
    }
}