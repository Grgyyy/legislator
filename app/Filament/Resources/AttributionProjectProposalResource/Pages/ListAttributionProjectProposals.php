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
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new AttributionProjectProposalImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'Target data have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the Target data: ' . $e->getMessage());
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
