<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\ProvinceResource;
use App\Models\Region;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;

class ProvinceUnderRegion extends ListRecords
{
    protected static string $resource = ProvinceResource::class;

    public function getTitle(): string
    {
        $regionId = $this->getRegionId();
        $region = Region::find($regionId);

        return $region ? "Provinces - {$region->name}" : "Provinces";
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()->where('region_id', $this->getRegionId());
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getRegionId(): ?int
    {
        return (int) request()->route('record');
    }
}
