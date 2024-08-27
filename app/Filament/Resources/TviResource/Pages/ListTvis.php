<?php

namespace App\Filament\Resources\TviResource\Pages;

use Filament\Actions;
use App\Imports\TviImport;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use App\Filament\Resources\TviResource;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;

class ListTvis extends ListRecords
{
    protected static string $resource = TviResource::class;

    protected static ?string $title = 'Institutions';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),

            Action::make('TviImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = storage_path('app/public/' . $data['attachment']);

                    Excel::import(new TviImport, $file);

                    Notification::make()
                        ->title('TVI Imported')
                        ->success()
                        ->send();
                })
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Institutions',
            'List'
        ];
    }
}
