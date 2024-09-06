<?php

namespace App\Filament\Resources\TvetResource\Pages;

use App\Filament\Resources\TvetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use App\Imports\TvetImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Exception;

class ListTvets extends ListRecords
{
    protected static string $resource = TvetResource::class;

    protected static ?string $title = 'TVET Sectors';

    public function getBreadcrumbs(): array
    {
        return [
            '/tvets' => 'TVET Sectors',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),
            Action::make('TvetImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    try {
                        Excel::import(new TvetImport, $file);
                        Notification::make()
                            ->title('Import Successful')
                            ->body('TVET Sector import successful!')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('TVET Sector import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
