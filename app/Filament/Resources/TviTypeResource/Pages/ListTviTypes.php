<?php

namespace App\Filament\Resources\TviTypeResource\Pages;

use App\Filament\Resources\TviTypeResource;
use App\Imports\TviTypeImport;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ListTviTypes extends ListRecords
{
    protected static string $resource = TviTypeResource::class;

    protected static ?string $title = 'Institution Types';
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/tvi-types' => 'Institution Types',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('TviTypeImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new TviTypeImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The institution types have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the institution types: ' . $e->getMessage());
                    }
                }),
        ];
    }
}
