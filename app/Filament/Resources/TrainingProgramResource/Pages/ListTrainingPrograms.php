<?php

namespace App\Filament\Resources\TrainingProgramResource\Pages;

use App\Filament\Resources\TrainingProgramResource;
use App\Imports\TrainingProgramsImport;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ListTrainingPrograms extends ListRecords
{
    protected static string $resource = TrainingProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()  
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('TrainingProgramsImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    
                    try {
                        Excel::import(new TrainingProgramsImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The training programs have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the training programs: ' . $e->getMessage());
                    }
                }),
        ];
    }
}