<?php

namespace App\Filament\Clusters\Sectors\Resources\AbddResource\Pages;

use App\Filament\Clusters\Sectors\Resources\AbddResource;
use App\Imports\AbddImport;
use Filament\Resources\Pages\ListRecords;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ListAbdds extends ListRecords
{
    protected static string $resource = AbddResource::class;

    protected static ?string $title = 'ABDD Sectors';

    public function getBreadcrumbs(): array
    {
        return [
            '/sectors/abdds' => 'ABDD Sectors',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('AbddImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    
                    try {
                        Excel::import(new AbddImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The ABDD sectors have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the ABDD sectors: ' . $e->getMessage());
                    }
                }),
        ];
    }
}