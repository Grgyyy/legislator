<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\RegionResource;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RegionImport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Notification;

class ListRegions extends ListRecords
{
    protected static string $resource = RegionResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

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
                    FileUpload::make('file')
                        ->label('Import Regions')
                        ->required()
                        ->markAsRequired(false)
                        ->disk('local') // Ensure it uses local disk
                        ->directory('imports') // Save files in storage/app/imports/
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),
                ])
                ->action(function (array $data) {
                    if (isset($data['file']) && is_string($data['file'])) {
                        $filePath = storage_path('app/' . $data['file']); // Get the full file path

                        try {
                            Excel::import(new RegionImport, $filePath);
                            NotificationHandler::sendSuccessNotification('Import Successful', 'The provinces have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the provinces: ' . $e->getMessage());
                        } finally {
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                }),
        ];
    }
}
