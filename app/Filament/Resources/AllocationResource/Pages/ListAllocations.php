<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use Exception;
use Filament\Actions\Action;
use App\Exports\AllocationExport;
use App\Imports\AllocationImport;
use Filament\Actions\CreateAction;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\AllocationResource;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Filament\Resources\AllocationResource\Widgets\StatsOverview;

class ListAllocations extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = AllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('AllocationImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new AllocationImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The allocations have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the allocations: ' . $e->getMessage());
                    }
                }),


            // Action::make('AllocationExport')
            //     ->label('Export')
            //     ->icon('heroicon-o-document-arrow-down')
            //     ->action(function (array $data) {
            //         try {
            //             return Excel::download(new AllocationExport, 'allocation_export.xlsx');
            //         } catch (ValidationException $e) {
            //             NotificationHandler::sendErrorNotification('Export Failed', 'Validation failed: ' . $e->getMessage());
            //         } catch (Exception $e) {
            //             NotificationHandler::sendErrorNotification('Export Failed', 'Spreadsheet error: ' . $e->getMessage());
            //         } catch (Exception $e) {
            //             NotificationHandler::sendErrorNotification('Export Failed', 'An unexpected error occurred: ' . $e->getMessage());
            //         }
            //     }),

        ];

    }

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
        ];
    }
}

