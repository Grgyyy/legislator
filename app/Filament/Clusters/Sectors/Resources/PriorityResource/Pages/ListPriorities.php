<?php

namespace App\Filament\Clusters\Sectors\Resources\PriorityResource\Pages;

use App\Filament\Clusters\Sectors\Resources\PriorityResource;
use App\Imports\TenPrioImport;
use Filament\Resources\Pages\ListRecords;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ListPriorities extends ListRecords
{
    protected static string $resource = PriorityResource::class;

    protected static ?string $title = 'Top Ten Priority Sectors';

    public function getBreadcrumbs(): array
    {
        return [
            '/sectors/priorities' => 'Top Ten Priority Sectors',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('TenPrioImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                    ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new TenPrioImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The priority sectors have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the priority sectors: ' . $e->getMessage());
                    }
                }),
        ];
    }
}
