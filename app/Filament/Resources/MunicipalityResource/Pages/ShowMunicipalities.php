<?php
namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Models\Municipality;
use App\Filament\Resources\MunicipalityResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;

class ShowMunicipalities extends ListRecords
{
    protected static string $resource = MunicipalityResource::class;

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
        $municipalityId = $this->getMunicipalityId();
        $municipality = Municipality::with('district.province.region')->findOrFail($municipalityId);

        $district = $municipality->district->first();
        $province = $district->province;
        $region = $province->region;

        $breadcrumbs = [
            route('filament.admin.resources.regions.index', ['record' => $region->id]) => $region ? $region->name : 'Regions',
            route('filament.admin.resources.provinces.showProvince', ['record' => $province->id]) => $province ? $province->name : 'Provinces',
            route('filament.admin.resources.districts.showDistricts', ['record' => $district->id]) => $district ? $district->name : 'Districts',
            'Municipalities' => 'Municipalities',
            'List' => 'List',
        ];

        return $breadcrumbs;
    }

    protected function getHeaderActions(): array
    {
        $districtId = $this->getMunicipalityId();

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.municipalities.create', ['district_id' => $districtId])),
        ];
    }

    protected function getMunicipalityId(): ?int
    {
        $municipalityId = request()->route('record') ?? session('municipality_id');

        if ($municipalityId) {
            session(['municipality_id' => $municipalityId]);
        }

        return (int) $municipalityId;
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
