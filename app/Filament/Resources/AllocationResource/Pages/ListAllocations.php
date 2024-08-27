<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use App\Imports\AllocationImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\AllocationResource;


class ListAllocations extends ListRecords
{
    protected static string $resource = AllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),

            Action::make('AllocationImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = storage_path('app/public/' . $data['attachment']);

                    Excel::import(new AllocationImport, $file);

                    Notification::make()
                        ->title('Allocation Imported')
                        ->success()
                        ->send();
                })
        ];
    }
}
