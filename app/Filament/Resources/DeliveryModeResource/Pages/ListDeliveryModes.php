<?php

namespace App\Filament\Resources\DeliveryModeResource\Pages;

use Exception;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use App\Imports\DeliveryModeImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\DeliveryModeResource;

class ListDeliveryModes extends ListRecords
{
    protected static string $resource = DeliveryModeResource::class;
    
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

            Action::make('DeliveryModeImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('file')
                        ->label('Import Delivery Mode')
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
                            Excel::import(new DeliveryModeImport, $filePath);
                            NotificationHandler::sendSuccessNotification('Import Successful', 'The Delivery Modes have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the delivery modes: ' . $e->getMessage());
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
