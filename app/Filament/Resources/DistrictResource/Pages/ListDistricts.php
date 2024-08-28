<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use App\Imports\DistrictImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\DistrictResource;

class ListDistricts extends ListRecords
{
    protected static string $resource = DistrictResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),

            Action::make('DistrictImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = storage_path('app/public/' . $data['attachment']);

                    Excel::import(new DistrictImport, $file);

                    Notification::make()
                        ->title('District Imported')
                        ->success()
                        ->send();
                })
        ];
    }
}
