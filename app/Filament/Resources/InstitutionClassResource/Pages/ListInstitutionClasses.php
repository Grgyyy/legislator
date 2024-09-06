<?php

namespace App\Filament\Resources\InstitutionClassResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\InstitutionClassResource;
use Filament\Actions\Action;
use App\Imports\InstitutionClassImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Exception;
use Filament\Actions\CreateAction;

class ListInstitutionClasses extends ListRecords
{
    protected static string $resource = InstitutionClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),
            Action::make('InstitutionClassImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    try {
                        Excel::import(new InstitutionClassImport, $file);
                        Notification::make()
                            ->title('Import Successful')
                            ->body('Institution Class B import successful!')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Institution Class B import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
