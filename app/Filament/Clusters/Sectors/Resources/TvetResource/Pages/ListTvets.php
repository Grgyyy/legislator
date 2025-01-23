<?php

namespace App\Filament\Clusters\Sectors\Resources\TvetResource\Pages;

use App\Filament\Clusters\Sectors\Resources\TvetResource;
use App\Imports\TvetImport;
use Filament\Resources\Pages\ListRecords;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ListTvets extends ListRecords
{
    protected static string $resource = TvetResource::class;

    protected static ?string $title = 'TVET Sectors';

    public function getBreadcrumbs(): array
    {
        return [
            '/sectors/tvets' => 'TVET Sectors',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('TvetImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-down')
                ->form([
                    FileUpload::make('attachment')
                        ->required()
                        ->markAsRequired(false),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    
                    try {
                        Excel::import(new TvetImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The TVET sectors have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the TVET sectors: ' . $e->getMessage());
                    }
                }),
        ];
    }
}