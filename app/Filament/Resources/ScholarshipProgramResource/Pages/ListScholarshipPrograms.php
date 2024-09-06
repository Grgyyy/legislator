<?php

namespace App\Filament\Resources\ScholarshipProgramResource\Pages;

use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use App\Imports\ScholarshipProgramImport;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ScholarshipProgramResource;
use Exception;
use Filament\Actions\CreateAction;

class ListScholarshipPrograms extends ListRecords
{
    protected static string $resource = ScholarshipProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),
            Action::make('ScholarshipProgramImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    try {
                        Excel::import(new ScholarshipProgramImport, $file);
                        Notification::make()
                            ->title('Import Successful')
                            ->body('Scholarship Program import successful!')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Scholarship Program import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
