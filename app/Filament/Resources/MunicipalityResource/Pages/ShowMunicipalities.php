<?php
namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Models\Municipality;
use App\Filament\Resources\MunicipalityResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowMunicipalities extends ListRecords
{
    protected static string $resource = MunicipalityResource::class;

    public function getBreadcrumbs(): array
    {
        $municipalityId = $this->getMunicipalityId();
        $municipality = Municipality::with('district.province.region')->find($municipalityId);

        // Handle case where municipality or district is not found
        if (!$municipality || $municipality->district->isEmpty()) {
            return [
                'Regions',
                'Provinces',
                'Districts',
                'Municipalities',
                'List',
            ];
        }

        $breadcrumbs = [];

        // Loop through districts, assuming the municipality has multiple districts
        $district = $municipality->district->first(); // Get the first district from the collection
        if ($district) {
            $province = $district->province;
            $region = $province->region;

            // Breadcrumbs structure
            $breadcrumbs[route('filament.admin.resources.regions.index', ['record' => $region->id])] = $region->name;
            $breadcrumbs[route('filament.admin.resources.provinces.showProvince', ['record' => $province->id])] = $province->name;
            $breadcrumbs[route('filament.admin.resources.districts.showDistricts', ['record' => $district->id])] = $district->name;
        }

        $breadcrumbs['Municipalities'] = 'Municipalities';
        $breadcrumbs['List'] = 'List';

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
        return (int) request()->route('record');
    }
}
