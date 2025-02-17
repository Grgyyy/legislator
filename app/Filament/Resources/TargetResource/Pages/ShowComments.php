<?php

namespace App\Filament\Resources\TargetResource\Pages;

use App\Filament\Resources\TargetCommentResource;
use App\Filament\Resources\TargetHistoryResource;
use App\Models\Province;
use App\Models\Region;
use App\Models\TargetComment;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
    
    public function mount(): void
    {
        $targetId = $this->getTargetId();
        $userId = auth()->id();

        $unreadComments = TargetComment::where('target_id', $targetId)
            ->whereDoesntHave('readByUsers', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->get();

        foreach ($unreadComments as $comment) {
            $comment->readByUsers()->create(['user_id' => $userId]);
        }

        $this->dispatch('commentsRead');
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
