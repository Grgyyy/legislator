<?php

namespace App\Filament\Resources\QualificationTitleResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use App\Imports\ProvinceImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use App\Imports\QualificationTitleImport;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\QualificationTitleResource;
use Exception;

class ListQualificationTitles extends ListRecords
{
    protected static string $resource = QualificationTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
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
                        ->title('Province Imported')
                        ->success()
                        ->send();
                    }
                    catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Qualification Title Import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                    ;

                    
                })

        ];
    }

}
