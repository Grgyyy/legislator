<?php

namespace App\Filament\Resources\InstitutionClassResource\Pages;

use App\Filament\Resources\InstitutionClassResource;
use App\Imports\InstitutionClassImport;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ListInstitutionClasses extends ListRecords
{
    protected static string $resource = InstitutionClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make() 
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('InstitutionClassImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    
                    try {
                        Excel::import(new InstitutionClassImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The institution class have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the institution class: ' . $e->getMessage());
                    }
                }),
        ];
    }
}