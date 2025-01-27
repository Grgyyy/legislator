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
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new ProjectProposalImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'Target data have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the Target data: ' . $e->getMessage());
                    }
                }),
        ];
    }
}
