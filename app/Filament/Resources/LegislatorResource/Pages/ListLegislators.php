<?php

namespace App\Filament\Resources\LegislatorResource\Pages;

use App\Exports\LegislatorExport;
use App\Filament\Resources\LegislatorResource;
use App\Imports\LegislatorImport;
use App\Services\NotificationHandler;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
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




            Action::make('LegislatorImport')
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

            Action::make('LegislatorExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-up')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new LegislatorExport, now()->format('m-d-Y') . ' - ' . 'Legislators.xlsx');
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
