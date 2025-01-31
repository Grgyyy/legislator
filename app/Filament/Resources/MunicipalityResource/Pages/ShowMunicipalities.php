<?php
namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Models\District;
use App\Filament\Resources\MunicipalityResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;

class ShowMunicipalities extends ListRecords
{
    protected static string $resource = MunicipalityResource::class;

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
        $distictId = $this->getDistrictId();
        $districtRecord = District::find($distictId);

        $province = $districtRecord->province;
        $region = $province->region;

        $breadcrumbs = [
            route('filament.admin.resources.provinces.showProvince', ['record' => $region->id]) => $region ? $region->name : 'Regions',
            route('filament.admin.resources.districts.showDistricts', ['record' => $province->id]) => $province ? $province->name : 'Provinces',
            route('filament.admin.resources.municipalities.show', ['record' => $districtRecord->id]) => $districtRecord ? $districtRecord->name : 'Provinces',
            'Municipalities' => 'Municipalities',
            'List' => 'List',
        ];

        return $breadcrumbs;
    }

    protected function getHeaderActions(): array
    {
        $districtId = $this->getDistrictId();

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.municipalities.create', ['district_id' => $districtId])),
        ];
    }

    protected function getTableQuery(): Builder|null
    {
        $districtId = $this->getDistrictId();
        return parent::getTableQuery()->whereHas('district', function (Builder $query) use ($districtId) {
            $query->where('district_id', $districtId);
        });
    }
    
    protected function getDistrictId(): ?int
    {
        $districtId = request()->route('record') ?? session('district_id');
    
        if ($districtId) {
            session(['district_id' => $districtId]);
        }
    
        return (int) $districtId;
    }
}