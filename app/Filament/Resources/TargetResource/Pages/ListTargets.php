<?php

namespace App\Filament\Resources\TargetResource\Pages;

use Filament\Actions\Action;
use App\Imports\TargetImport;
use App\Imports\AdminTargetImport;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Log;
use App\Exports\PendingTargetExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use PhpOffice\PhpSpreadsheet\Exception;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\TargetResource;
use Maatwebsite\Excel\Validators\ValidationException;

class ListTargets extends ListRecords
{
    protected static string $resource = TargetResource::class;

    protected ?string $heading = 'Pending Targets';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.targets.index') => 'Pending Targets',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),

            Action::make('PendingTargetExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new PendingTargetExport, 'pending_target_export.xlsx');
                    } catch (ValidationException $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Validation failed: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Spreadsheet error: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'An unexpected error occurred: ' . $e->getMessage());
                    }
                }),



            Action::make('TargetImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new TargetImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'Target data have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the Target data: ' . $e->getMessage());
                    }
                }),

            Action::make('AdminTargetImport')
                ->label('Admin Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new AdminTargetImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'Target data has been successfully imported from the file without impacting the allocation or skill priority slots.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the Target data: ' . $e->getMessage());
                    }
                }),
        ];
    }
}
