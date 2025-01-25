<?php

namespace App\Filament\Resources\TargetResource\Pages;

use App\Filament\Resources\TargetCommentResource;
use App\Filament\Resources\TargetHistoryResource;
use App\Models\Province;
use App\Models\Region;
use App\Models\TargetComment;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowComments extends ListRecords
{
    protected static string $resource = TargetCommentResource::class;

    protected ?string $heading = 'Target Comments';

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

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }
    // public function getBreadcrumbs(): array
    // {
    //     $regionId = $this->getRegionId();
    //     $provinceId = $this->getProvinceId();

    //     $region = Region::find($regionId);
    //     $province = Province::find($provinceId);

    //     $region_id = $province->region->id;

    //     return [

    //         route('filament.admin.resources.regions.show_provinces', ['record' => $region_id]) => $province ? $province->region->name : 'Regions',
    //         route('filament.admin.resources.provinces.showMunicipalities', ['record' => $provinceId]) => $province ? $province->name : 'Provinces',
    //         'Municipalities',
    //         'List'
    //     ];
    // }

    protected function getHeaderActions(): array
    {
        $targetId = $this->getTargetId();

        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New')
                ->url(route('filament.admin.resources.target-comments.create', ['record' => $targetId]))
        ];
    }

    protected function getTargetId(): ?int
    {
        return (int) request()->route('record');
    }

    // protected function getRegionId(): ?int
    // {
    //     return (int) request()->route('record');
    // }
}
