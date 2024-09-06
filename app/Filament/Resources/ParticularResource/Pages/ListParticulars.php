<?php

namespace App\Filament\Resources\ParticularResource\Pages;

use Filament\Actions\Action;
use App\Imports\ParticularImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ParticularResource;
use Exception;
use Filament\Actions\CreateAction;

class ListParticulars extends ListRecords
{
    protected static string $resource = ParticularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),
            Action::make('ParticularImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    try {
                        Excel::import(new ParticularImport, $file);
                        Notification::make()
                            ->title('Import Successful')
                            ->body('Particulars import successful!')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Particulars import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];

    }
}
