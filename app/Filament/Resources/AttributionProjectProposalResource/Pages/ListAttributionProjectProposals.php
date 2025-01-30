<?php

namespace App\Filament\Resources\AttributionProjectProposalResource\Pages;

use App\Filament\Resources\AttributionProjectProposalResource;
use App\Imports\AttributionProjectProposalImport;
use App\Imports\ProjectProposalImport;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use PhpOffice\PhpSpreadsheet\Exception;
use Filament\Forms\Components\FileUpload;


class ListAttributionProjectProposals extends ListRecords
{
    protected static string $resource = AttributionProjectProposalResource::class;

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
                }),
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
