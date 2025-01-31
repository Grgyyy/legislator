<?php

namespace App\Filament\Resources\LegislatorResource\Pages;

use Exception;
use Filament\Actions\Action;
use App\Exports\LegislatorExport;
use App\Imports\LegislatorImport;
use Filament\Actions\CreateAction;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\LegislatorResource;
use Maatwebsite\Excel\Validators\ValidationException;

class ListLegislators extends ListRecords
{
    protected static string $resource = LegislatorResource::class;

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

            Action::make('LegislatorExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new LegislatorExport, 'legislator_export.xlsx');
                    } catch (ValidationException $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Validation failed: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Spreadsheet error: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'An unexpected error occurred: ' . $e->getMessage());
                    };
                }),




            Action::make('LegislatorImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-down')
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
                            Excel::import(new LegislatorImport, $filePath);
                            NotificationHandler::sendSuccessNotification('Import Successful', 'The legislators have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the legislators: ' . $e->getMessage());
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

// public function getTabs(): array
// {
//     return [
//         'All' => Tab::make(),
//         'Active' => Tab::make()
//             ->modifyQueryUsing(function ($query) {
//                 $query->where('status_id', '1');
//             })
//             ->badge(function () {
//                 return Legislator::where('status_id', '1')->count();
//             }),
//         'Inactive' => Tab::make()
//             ->modifyQueryUsing(function ($query) {
//                 $query->where('status_id', '2');
//             })
//             ->badge(function () {
//                 return Legislator::where('status_id', '2')->count();
//             }),
//     ];
// }
