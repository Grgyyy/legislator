<?php

namespace App\Filament\Resources\InstitutionClassResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\InstitutionClassImport;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\InstitutionClassResource;

class ListInstitutionClasses extends ListRecords
{
    protected static string $resource = InstitutionClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),

            Action::make('InstitutionClassImport')
                ->label('Import')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);


                    Excel::import(new InstitutionClassImport, $file);

                    Notification::make()
                        ->title('Institution Class Imported')
                        ->success()
                        ->send();
                })
        ];
    }
}
