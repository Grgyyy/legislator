<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Filament\Resources\ProvinceResource;
use App\Imports\ProvinceImport;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Notification;

class ListProvinces extends ListRecords
{
    protected static string $resource = ProvinceResource::class;
    
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

            Action::make('ProvinceImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-down')
                ->form([
                    FileUpload::make('file')
                        ->label('Import Provinces')
                        ->required()
                        ->markAsRequired(false)
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),
                ])
                ->action(function (array $data) {
                    if (isset($data['file']) && is_string($data['file'])) {
                        $filePath = storage_path('app/' . $data['file']);

                        try {
                            Excel::import(new ProvinceImport, $filePath);
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