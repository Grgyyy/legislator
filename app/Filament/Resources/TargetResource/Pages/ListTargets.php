<?php

namespace App\Filament\Resources\TargetResource\Pages;

use Filament\Actions\Action;
use App\Imports\TargetImport;
use App\Imports\AdminTargetImport;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Log;
use App\Exports\PendingTargetExport;
use Illuminate\Support\Facades\Auth;
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
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

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

            // Action::make('PendingTargetExport')
            //     ->label('Export')
            //     ->icon('heroicon-o-document-arrow-down')
            //     ->action(function (array $data) {
            //         try {
            //             return Excel::download(new PendingTargetExport, 'pending_target_export.xlsx');
            //         } catch (ValidationException $e) {
            //             NotificationHandler::sendErrorNotification('Export Failed', 'Validation failed: ' . $e->getMessage());
            //         } catch (Exception $e) {
            //             NotificationHandler::sendErrorNotification('Export Failed', 'Spreadsheet error: ' . $e->getMessage());
            //         } catch (Exception $e) {
            //             NotificationHandler::sendErrorNotification('Export Failed', 'An unexpected error occurred: ' . $e->getMessage());
            //         }
            //     }),



            Action::make('TargetImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('file')
                        ->label('Import Targets')
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
                            Excel::import(new TargetImport, $filePath);
                            NotificationHandler::sendSuccessNotification('Import Successful', 'The Targets have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the targets: ' . $e->getMessage());
                        } finally {
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                }),

            Action::make('AdminTargetImport')
                ->label('Admin Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('file')
                        ->label('Import Targets as Admin')
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
                            Excel::import(new AdminTargetImport, $filePath);
                            NotificationHandler::sendSuccessNotification('Import Successful', 'The district have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the provinces: ' . $e->getMessage());
                        } finally {
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                })
                // ->visible(fn() => Auth::user()->hasRole('Super Admin')),
                ->visible(false)
        ];
    }
}
