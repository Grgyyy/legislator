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
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new DeliveryModeImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The Delivery Mode have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the Delivery Mode: ' . $e->getMessage());
                    }
                }),
        ];
    }
}
