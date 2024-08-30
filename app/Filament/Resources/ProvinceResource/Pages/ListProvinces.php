<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use App\Imports\RegionImport;
use App\Imports\ProvinceImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProvinceResource;


class ListProvinces extends ListRecords
{
    protected static string $resource = ProvinceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),



            Action::make('ProvinceImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = storage_path('app/public/' . $data['attachment']);

                    Excel::import(new ProvinceImport, $file);

                    Notification::make()
                        ->title('Province Imported')
                        ->success()
                        ->send();
                })



        ];
    }
}
