<?php

namespace App\Filament\Resources\QualificationTitleResource\Pages;

use App\Filament\Resources\QualificationTitleResource;
use App\Imports\QualificationTitleImport;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ListQualificationTitles extends ListRecords
{
    protected static string $resource = QualificationTitleResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.qualification-titles.index') => 'Schedule of Cost',
            'List',
        ];
    }

    protected ?string $heading = 'Schedule of Cost';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('QualificationTitleImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = storage_path('app/public/' . $data['attachment']);

                    try {
                        Excel::import(new QualificationTitleImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The qualification titles have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the qualification titles: ' . $e->getMessage());
                    }
                })
        ];
    }
}
