<?php

namespace App\Filament\Resources\TviClassResource\Pages;

use Exception;
use Filament\Actions\Action;
use App\Imports\TviClassImport;
use Filament\Actions\CreateAction;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use App\Exports\InsitutionClassAExport;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\TviClassResource;
use Maatwebsite\Excel\Validators\ValidationException;

class ListTviClasses extends ListRecords
{
    protected static string $resource = TviClassResource::class;

    protected static ?string $title = 'Institution Classes';

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/tvi-classes' => 'Institution Classes',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('InsitutionClassAExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new InsitutionClassAExport, 'institution_class_a_export.xlsx');
                    } catch (ValidationException $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Validation failed: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Spreadsheet error: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'An unexpected error occurred: ' . $e->getMessage());
                    };
                }),


            Action::make('TviClassImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('file')
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
                            Excel::import(new TviClassImport, $filePath);
                            NotificationHandler::sendSuccessNotification('Import Successful', 'The Institution Classes (A) have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the no. of institution classes (A): ' . $e->getMessage());
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
