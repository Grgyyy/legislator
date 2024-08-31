<?php

namespace App\Filament\Resources\ScholarshipProgramResource\Pages;

use Filament\Actions;
use App\Models\Region;
use Filament\Actions\Action;
use App\Imports\RegionImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use App\Imports\ScholarshipProgramImport;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\RegionResource;
use App\Filament\Resources\ScholarshipProgramResource;
use Exception;

class ListScholarshipPrograms extends ListRecords
{
    protected static string $resource = ScholarshipProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
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
                            ->body('Scholarship Program Import successful!')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Scholarship Program Import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];





    }
}
