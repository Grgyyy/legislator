<?php

namespace App\Filament\Resources\TviTypeResource\Pages;

use App\Exports\InsitutionTypeExport;
use App\Filament\Resources\TviTypeResource;
use App\Imports\TviTypeImport;
use App\Services\NotificationHandler;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ListTviTypes extends ListRecords
{
    protected static string $resource = TviTypeResource::class;

    protected static ?string $title = 'Institution Types';

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/institution-types' => 'Institution Types',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('TviTypeImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-down')
                ->form([
                    FileUpload::make('file')
                        ->label('')
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
                            Excel::import(new TviTypeImport, $filePath);
                            NotificationHandler::sendSuccessNotification('Import Successful', 'The institution types have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the institution types: ' . $e->getMessage());
                        } finally {
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                }),

            Action::make('InsitutionTypeExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-up')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new InsitutionTypeExport, now()->format('m-d-Y') . ' - ' . 'Institution Types.xlsx');
                    } catch (ValidationException $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Validation failed: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Spreadsheet error: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'An unexpected error occurred: ' . $e->getMessage());
                    };
                }),
        ];
    }
}
