<?php

namespace App\Filament\Resources\AbddResource\Pages;

use Exception;
use App\Imports\AbddImport;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use App\Filament\Resources\AbddResource;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListAbdds extends ListRecords
{
    protected static string $resource = AbddResource::class;

    protected static ?string $title = 'ABDD Sectors';

    public function getBreadcrumbs(): array
    {
        return [
            '/abdds' => 'ABDD Sectors',
            'List'
        ];
    }


    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),

            Action::make('AbddImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    try {
                        Excel::import(new AbddImport, $file);
                        Notification::make()
                            ->title('Import Successful')
                            ->body('ABDD Sector Import successful!')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('ABDD Sector Import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
