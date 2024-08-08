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

    protected function getTableQuery(): ?Builder
    {
        // Retrieve the region ID from the route parameter
        return parent::getTableQuery()->where('region_id', $this->getRegionId());
    }

    public function getHeader(): ?View
    {
        // Retrieve the region ID from the route parameters
        $regionId = $this->getRegionId();
        
        // Fetch the region name based on the region ID
        $region = Region::find($regionId);

        // Return a view with the header text or null
        return ViewFacade::make('filament.region-header', [
            'header' => $region ? "Provinces - {$region->name}" : "Provinces",
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Province'),
        ];
    }

    protected function getRegionId(): ?int
    {
        // Retrieve the region ID from the route parameters
        return (int) request()->route('record');
    }
}
