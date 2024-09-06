<?php

namespace App\Filament\Resources\QualificationTitleResource\Pages;

use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use App\Imports\QualificationTitleImport;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\QualificationTitleResource;
use Exception;
use Filament\Actions\CreateAction;

class ListQualificationTitles extends ListRecords
{
    protected static string $resource = QualificationTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),
            Action::make('QualificationTitleImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = storage_path('app/public/' . $data['attachment']);
                    try {
                        Excel::import(new QualificationTitleImport, $file);
                        Notification::make()
                        ->title('Import Successful')
                        ->body('Qualification Title import successful!')
                        ->success()
                        ->send();
                    }
                    catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Qualification Title import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    };                    
                })

        ];
    }

}
