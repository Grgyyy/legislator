<?php

namespace App\Filament\Resources\InstitutionRecognitionResource\Pages;

use Exception;
use Filament\Actions\Action;
use App\Imports\InstitutionRecognitionImport;
use Filament\Actions\CreateAction;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\InstitutionRecognitionResource;

class ListInstitutionRecognitions extends ListRecords
{
    protected static string $resource = InstitutionRecognitionResource::class;

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

            Action::make('InstitutionRecognitionImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new InstitutionRecognitionImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The Institution Recognition Title have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the Institution Recognition Title: ' . $e->getMessage());
                    }
                }),
        ];
    }
}
