<?php

namespace App\Filament\Resources\ToolkitResource\Pages;

use App\Filament\Resources\ToolkitResource;
use App\Imports\NoOfToolkitsImport;
use App\Imports\ToolkitImport;
use Exception;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use Filament\Forms\Components\FileUpload;

class ListToolkits extends ListRecords
{
    protected static string $resource = ToolkitResource::class;
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New')
            ,

            Action::make('ToolkitsImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('file')
                        ->label('Import District')
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
                            Excel::import(new ToolkitImport, $filePath);
                            NotificationHandler::sendSuccessNotification('Import Successful', 'The Toolkits have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the toolkits: ' . $e->getMessage());
                        } finally {
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                }),

            Action::make('ToolkitsSlotsImport')
                ->label('Import No. of Toolkits')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('file')
                        ->label('Import District')
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
                            Excel::import(new NoOfToolkitsImport, $filePath);
                            NotificationHandler::sendSuccessNotification('Import Successful', 'The No. of Toolkits have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the no. of toolkits: ' . $e->getMessage());
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
