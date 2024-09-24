<?php

namespace App\Filament\Resources\SubParticularResource\Pages;

use App\Filament\Resources\SubParticularResource;
use App\Imports\ParticularTypesImport;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Imports\PartylistImport;

class ListSubParticulars extends ListRecords
{
    protected static string $resource = SubParticularResource::class;

    protected static ?string $title = 'Particular Types';

    public function getBreadcrumbs(): array
    {
        return [
            '/sub-particulars' => 'Particular Types',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),
            Action::make('RegionImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    try {
                        Excel::import(new ParticularTypesImport, $file);
                        Notification::make()
                            ->title('Import Successful')
                            ->body('Particular Types import successful!')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Particular Types import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
            }),
        ];
    }
}
