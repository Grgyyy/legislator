<?php

namespace App\Filament\Resources\AttributionTargetResource\Pages;

use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use App\Exports\AttributionTargetExport;
use App\Imports\AttributionTargetImport;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Imports\AdminAttributionTargetImport;
use App\Filament\Resources\AttributionTargetResource;
use Maatwebsite\Excel\Validators\ValidationException;


class ListAttributionTargets extends ListRecords
{
    protected static string $resource = AttributionTargetResource::class;
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),

            // Action::make('AttributionTargetExport')
            //     ->label('Export')
            //     ->icon('heroicon-o-document-arrow-down')
            //     ->action(function (array $data) {
            //         try {
            //             return Excel::download(new AttributionTargetExport, 'attribution_target_export.xlsx');
            //         } catch (ValidationException $e) {
            //             NotificationHandler::sendErrorNotification('Export Failed', 'Validation failed: ' . $e->getMessage());
            //         } catch (Exception $e) {
            //             NotificationHandler::sendErrorNotification('Export Failed', 'Spreadsheet error: ' . $e->getMessage());
            //         } catch (Exception $e) {
            //             NotificationHandler::sendErrorNotification('Export Failed', 'An unexpected error occurred: ' . $e->getMessage());
            //         }
            //     }),


            Action::make('AttributionTargetImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new AttributionTargetImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'Target data have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the Target data: ' . $e->getMessage());
                    }
                }),

            Action::make('AdminAttributionTargetImport')
                ->label('Admin Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new AdminAttributionTargetImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'Target data have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the Target data: ' . $e->getMessage());
                    }
                })
                ->visible(fn() => Auth::user()->hasRole('Super Admin')),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.attribution-targets.index') => 'Attribution Targets',
            'List'
        ];
    }

    protected static ?string $title = 'Attribution Targets';
}
