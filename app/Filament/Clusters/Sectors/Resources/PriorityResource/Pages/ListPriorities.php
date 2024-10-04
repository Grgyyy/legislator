<?php

namespace App\Filament\Clusters\Sectors\Resources\PriorityResource\Pages;

use App\Filament\Clusters\Sectors\Resources\PriorityResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
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
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),
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
                            ->body('Ten Priority Sector import successful!')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Ten Priority Sector import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
