<?php
namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\ProvinceResource;
use App\Models\Region;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use App\Filament\Resources\RegionResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShowProvinces extends ListRecords
{
    protected static string $resource = ProvinceResource::class;

    protected ?string $heading = 'Provinces';

    public function getBreadcrumbs(): array
    {
        $regionId = $this->getRegionId();

        $region = Region::find($regionId);

        return [
            route('filament.admin.resources.regions.show_provinces', ['record' => $regionId]) => $region ? $region->name : 'Regions',
            'Provinces',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        $regionId = $this->getRegionId();

        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New')
        ];
    }

    protected function getRegionId(): ?int
    {
        return (int) request()->route('record');
    }
}
