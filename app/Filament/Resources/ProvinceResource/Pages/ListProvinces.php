<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use Filament\Actions\Action;
use App\Imports\ProvinceImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProvinceResource;
use Exception;
use Filament\Actions\CreateAction;

class ListProvinces extends ListRecords
{
    protected static string $resource = ProvinceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),
            Action::make('ProvinceImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    try {
                        Excel::import(new ProvinceImport, $file);
                        Notification::make()
                            ->title('Import Successful')
                            ->body('Province import successful!')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Training Program import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
