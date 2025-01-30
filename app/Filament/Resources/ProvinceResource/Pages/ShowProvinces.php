<?php
namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Models\Region;
use App\Filament\Resources\ProvinceResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;

class ShowProvinces extends ListRecords
{
    protected static string $resource = ProvinceResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
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
    
    public function getBreadcrumbs(): array
    {
        $regionId = $this->getRegionId();

        $region = Region::find($regionId);

        return [
            route('filament.admin.resources.provinces.showProvince', ['record' => $region->id]) => $region ? $region->name : 'Regions',
            'Provinces',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        $regionId = $this->getRegionId();

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.provinces.create', ['region_id' => $regionId])),
        ];
    }

    protected function getTableQuery(): Builder|null
    {
        $regionId = $this->getRegionId();
        
        return parent::getTableQuery()->where('region_id', $regionId);
    }

    protected function getRegionId(): ?int
    {
        $regionId = request()->route('record') ?? session('region_id');

        if ($regionId) {
            session(['region_id' => $regionId]);
        }

        return (int) $regionId;
    }
}