<?php

namespace App\Filament\Resources\FundSourceResource\Pages;

use App\Exports\FundSourceExport;
use App\Filament\Resources\FundSourceResource;
use App\Imports\FundSourceImport;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use PhpOffice\PhpSpreadsheet\Exception;

class ListFundSources extends ListRecords
{
    protected static string $resource = FundSourceResource::class;

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

            Action::make('FundSourceImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-down')
                ->form([
                    FileUpload::make('file')
                        ->label('Import')
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
                            Excel::import(new FundSourceImport, $filePath);

                            NotificationHandler::sendSuccessNotification('Import Successful', 'The fund sources have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the fund sources: ' . $e->getMessage());
                        } finally {
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                }),

            Action::make('FundSourceExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-up')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new FundSourceExport, now()->format('m-d-Y') . ' - ' . 'Fund Source Export.xlsx');
                    } catch (ValidationException $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Validation failed: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Spreadsheet error: ' . $e->getMessage());
                    }
                }),
        ];
    }
}