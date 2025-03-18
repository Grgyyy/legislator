<?php

namespace App\Filament\Resources\AttributionTargetResource\Pages;

use App\Exports\AttributionTargetExport;
use App\Filament\Resources\AttributionTargetResource;
use App\Imports\AdminAttributionTargetImport;
use App\Imports\AttributionTargetImport;
use App\Services\NotificationHandler;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
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
                ->label('New')
                ->visible(fn() => !Auth::user()->hasRole(['SMD Focal', 'RO'])),

            Action::make('AttributionTargetImport')
                ->label('')
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
                            Excel::import(new AttributionTargetImport, $filePath);
                            NotificationHandler::sendSuccessNotification('Import Successful', 'The attribution targets have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the attribution targets: ' . $e->getMessage());
                        } finally {
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                })
                ->visible(fn() => !Auth::user()->hasRole(['SMD Focal', 'RO'])),

            Action::make('AttributionTargetExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-up')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new AttributionTargetExport, now()->format('m-d-Y') . ' - ' . 'Pending Attribution Targets.xlsx');
                    } catch (ValidationException $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Validation failed: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Spreadsheet error: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'An unexpected error occurred: ' . $e->getMessage());
                    }
                }),
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
