<?php

namespace App\Filament\Resources\ProjectProposalTargetResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use App\Imports\TargetImport;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Auth;

use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use App\Imports\ProjectProposalImport;
use PhpOffice\PhpSpreadsheet\Exception;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Exports\ProjectProposalTargetExport;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Filament\Resources\ProjectProposalTargetResource;

class ListProjectProposalTargets extends ListRecords
{
    protected static string $resource = ProjectProposalTargetResource::class;

    protected static ?string $title = 'Project Proposals';

    public function getBreadcrumbs(): array
    {
        return [
            '/project-proposal-targets' => 'Project Proposals',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->visible(fn() => !Auth::user()->hasRole('SMD Focal')),


            Action::make('ProjectProposalTargetExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new ProjectProposalTargetExport, 'project_proposal_target_export.xlsx');
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
                            Excel::import(new ProjectProposalImport, $filePath);
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
}
