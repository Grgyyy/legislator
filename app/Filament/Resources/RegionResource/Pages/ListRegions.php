<?php

namespace App\Filament\Resources\RegionResource\Pages;

use Filament\Actions;
use App\Models\Region;
use Filament\Actions\Action;
use App\Imports\RegionImport;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\RegionResource;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;




class ListRegions extends ListRecords
{
    protected static string $resource = RegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),


            Action::make('RegionImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = storage_path('app/public/' . $data['attachment']);

                    Excel::import(new RegionImport, $file);

                    Notification::make()
                        ->title('Region Imported')
                        ->success()
                        ->send();
                })
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
