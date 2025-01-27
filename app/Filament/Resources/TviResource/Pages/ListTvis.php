<?php

namespace App\Filament\Resources\TviResource\Pages;

use App\Filament\Resources\TviResource;
use App\Imports\TviImport;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Support\Facades\Storage;

class ListTvis extends ListRecords
{
    protected static string $resource = TviResource::class;

    protected static ?string $title = 'Institutions';
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/tvis' => 'Institutions',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('TviImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $filePath = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new TviImport, $filePath);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The institution training programs have been successfully imported from the file.');

                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the institution training programs: ' . $e->getMessage());
                    } finally {
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }),
        ];
    }
}
