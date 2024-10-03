<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\RegionResource;
use App\Imports\RegionImport;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ListRegions extends ListRecords
{
    protected static string $resource = RegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('RegionImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    
                    try {
                        Excel::import(new RegionImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The regions have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the regions: ' . $e->getMessage());
                    }
                }),
        ];
    }
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