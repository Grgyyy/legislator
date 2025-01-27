<?php

namespace App\Filament\Resources\LearningModeResource\Pages;

use App\Filament\Resources\LearningModeResource;
use Filament\Actions;
use App\Imports\LearningModeImport;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ListLearningModes extends ListRecords
{
    protected static string $resource = LearningModeResource::class;
    
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

            Action::make('LearningModeImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new LearningModeImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The Learning Modes have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the Learning Modes: ' . $e->getMessage());
                    }
                }),
        ];
    }
}
