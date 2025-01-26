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
use Filament\Pages\Actions\ButtonAction;

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
                ->icon('heroicon-o-document-arrow-down')
                ->form([
                    FileUpload::make('attachment')
                        ->required()
                        ->markAsRequired(false),
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
            // ButtonAction::make('import')
            //     ->label('Import Regions')
            //     ->icon('heroicon-o-upload')
            //     ->action(fn() => $this->dispatchBrowserEvent('open-region-import-modal')),
        ];
    }
}