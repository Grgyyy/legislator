<?php

namespace App\Filament\Resources\TviClassResource\Pages;

use App\Filament\Resources\TviClassResource;
use App\Imports\TviClassImport;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ListTviClasses extends ListRecords
{
    protected static string $resource = TviClassResource::class;

    protected static ?string $title = 'Institution Classes';
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/tvi-classes' => 'Institution Classes',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('TviClassImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new TviClassImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The institution class have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the institution class: ' . $e->getMessage());
                    }
                }),
        ];
    }
}
