<?php

namespace App\Filament\Resources\AttributionProjectProposalResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;

use App\Imports\ProjectProposalImport;
use PhpOffice\PhpSpreadsheet\Exception;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Exports\AttributionProjectProposalExport;
use App\Imports\AttributionProjectProposalImport;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Filament\Resources\AttributionProjectProposalResource;


class ListAttributionProjectProposals extends ListRecords
{
    protected static string $resource = AttributionProjectProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->visible(fn() => !Auth::user()->hasRole('SMD Focal')),

            Action::make('AttributionProjectProposalExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new AttributionProjectProposalExport, 'attribution_project_proposal_target_export.xlsx');
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
                            Excel::import(new AttributionProjectProposalImport, $filePath);
                            NotificationHandler::sendSuccessNotification('Import Successful', 'The Attribution Targets have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the attribution targets: ' . $e->getMessage());
                        } finally {
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                })
                ->visible(fn() => !Auth::user()->hasRole('SMD Focal')),
        ];
    }

    protected static ?string $title = 'Attribution Project Proposals';

    public function getBreadcrumbs(): array
    {
        return [
            '/attribution-project-proposals' => 'Attribution Project Proposals',
            'List'
        ];
    }
}
