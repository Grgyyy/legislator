<?php

namespace App\Filament\Resources\TviClassResource\Pages;

use App\Filament\Resources\TviClassResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use App\Imports\TviClassImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Exception;
use Filament\Actions\CreateAction;

class ListTviClasses extends ListRecords
{
    protected static string $resource = TviClassResource::class;

    protected static ?string $title = 'Institution Classes';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),
            Action::make('TviClassImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    try {
                        Excel::import(new TviClassImport, $file);
                        Notification::make()
                            ->title('Import Successful')
                            ->body('Institution Class A import successful!')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Institution Class A import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Institution Classes',
            'List'
        ];
    }
}
