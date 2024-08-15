<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\RegionResource;
use App\Models\Region;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListRegions extends ListRecords
{
    protected static string $resource = RegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Region'),
        ];
    }
    // public function getTabs(): array
    // {
    //     return [
    //         'All' => Tab::make()
    //             ->badge(function () {
    //                 return Region::all()->count();
    //             }),
    //         '2023' => Tab::make()
    //             ->modifyQueryUsing(function ($query) {
    //                 $query->whereYear('created_at', '2023');
    //             })
    //             ->badge(function () {
    //                 return Region::whereYear('created_at', '2023')->count();
    //             }),

    //         '2024' => Tab::make()
    //             ->modifyQueryUsing(function ($query) {
    //                 $query->whereYear('created_at', '2024');
    //             })
    //             ->badge(function () {
    //                 return Region::whereYear('created_at', '2024')->count();
    //             }),

    //         'NULL' => Tab::make()
    //             ->modifyQueryUsing(function ($query) {
    //                 $query->whereNull('created_at');
    //             })
    //             ->badge(function () {
    //                 return Region::whereNull('created_at')->count();
    //             }),
    //     ];
    // }
}
