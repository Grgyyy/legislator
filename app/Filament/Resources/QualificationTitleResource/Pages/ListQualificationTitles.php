<?php

namespace App\Filament\Resources\QualificationTitleResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\QualificationTitleResource;
use App\Imports\QualificationTitleImport;

class ListQualificationTitles extends ListRecords
{
    protected static string $resource = QualificationTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Qualification Title'),
            // Action::make('importQualificationTitles')
            //     ->label('Import Qualification Title')
            //     ->form([
            //         FileUpload::make('attachment'),
            //     ])
            //     ->action(function (array $data) {
            //         $file = public_path('storage/' . $data['attachment']);

            //         Excel::import(new QualificationTitleImport, $file);
            //     })
        ];
    }
}
