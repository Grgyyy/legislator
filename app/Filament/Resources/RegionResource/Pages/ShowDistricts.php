<?php
namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\DistrictResource;
use App\Models\Region;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ShowDistricts extends ListRecords
{
    protected static string $resource = DistrictResource::class;

    protected ?string $heading = 'Districts';

    public function getBreadcrumbs(): array
    {
        $regionId = $this->getRegionId();
        
        $region = Region::find($regionId);
        
        return [
            'regions' => $region ? $region->name : 'Regions',
            'Districts',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        $regionId = $this->getRegionId();

        return [
            CreateAction::make()
                ->label('New District')
                ->url(route('filament.admin.resources.districts.create', ['region_id' => $regionId]))
        ];
    }

    protected function getRegionId(): ?int
    {
        return (int) request()->route('region_id');
    }
}
