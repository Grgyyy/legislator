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

class ShowProvinces extends ListRecords
{
    protected static string $resource = ProvinceResource::class;

    protected function getTableQuery(): ?Builder
    {
        // Retrieve the region ID from the route parameter
        return parent::getTableQuery()->where('region_id', $this->getRegionId());
    }

    protected ?string $heading = 'Provinces';

    public function getBreadcrumbs(): array
    {
        $regionId = $this->getRegionId();
        
        $region = Region::find($regionId);
        
        return [
            'regions' => $region ? $region->name : 'Regions',
            'Provinces',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        $regionId = $this->getRegionId(); // Make sure $regionId is available here

        return [
            CreateAction::make()
                ->label('New Province')
                ->url(route('filament.admin.resources.provinces.create', ['region_id' => $regionId])) // Pass region_id in the route
        ];
    }

    protected function getRegionId(): ?int
    {
        // Retrieve the region ID from the route parameters
        return (int) request()->route('record');
    }
}
