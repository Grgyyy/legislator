<?php

namespace App\Filament\Resources\ProjectProposalTargetResource\Pages;

use App\Filament\Resources\ProjectProposalTargetResource;
use App\Imports\ProjectProposalImport;
use App\Imports\TargetImport;
use Filament\Actions;
use Filament\Actions\CreateAction;

use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use PhpOffice\PhpSpreadsheet\Exception;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;

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
                ->icon('heroicon-m-plus'),

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
                }),
        ];
    }
}
