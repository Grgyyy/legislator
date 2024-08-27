<?php

namespace App\Filament\Resources\QualificationTitleResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\QualificationTitleResource;
use App\Imports\QualificationTitleImport;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Components\Tab;

class ListQualificationTitles extends ListRecords
{
    protected static string $resource = QualificationTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New')
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
