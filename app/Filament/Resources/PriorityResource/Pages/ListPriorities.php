<?php

namespace App\Filament\Resources\PriorityResource\Pages;

use App\Filament\Resources\PriorityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use App\Imports\TenPrioImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Exception;


class ListPriorities extends ListRecords
{
    protected static string $resource = PriorityResource::class;

    protected static ?string $title = 'Top Ten Priority Sectors';

    public function getBreadcrumbs(): array
    {
        return [
            '/priorities' => 'Top Ten Priority Sectors',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Action::make('TenPrioImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    try {
                        Excel::import(new TenPrioImport, $file);
                        Notification::make()
                            ->title('Import Successful')
                            ->body('Ten Priority Sector Import successful!')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Ten Priority Sector Import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
