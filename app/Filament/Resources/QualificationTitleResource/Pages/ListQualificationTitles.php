<?php

namespace App\Filament\Resources\QualificationTitleResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use App\Imports\ProvinceImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use App\Imports\QualificationTitleImport;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\QualificationTitleResource;

class ListQualificationTitles extends ListRecords
{
    protected static string $resource = QualificationTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),


            Action::make('QualificationTitleImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = storage_path('app/public/' . $data['attachment']);

                    Excel::import(new QualificationTitleImport, $file);

                    Notification::make()
                        ->title('Province Imported')
                        ->success()
                        ->send();
                })

        ];
    }



    // public function getTabs(): array
    // {
    //     return [
    //         'All' => Tab::make(),
    //         'TTSP' => Tab::make()->modifyQueryUsing(function ($query) {
    //             $query->where('scholarship_program_id', 1);
    //         }),
    //         'TWSP' => Tab::make()->modifyQueryUsing(function (Builder $query) {
    //             $query->where('scholarship_program_id', 2)->whereDate('created_at', 2024);
    //         }),

    //     ];
    // }
}
