<?php

namespace App\Filament\Resources\LegislatorResource\Pages;

use Filament\Actions;
use App\Models\Legislator;
use Filament\Actions\Action;
use App\Imports\LegislatorsImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Resources\Components\Tab;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\LegislatorResource;
use App\Imports\LegislatorImport;

class ListLegislators extends ListRecords
{
    protected static string $resource = LegislatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),


            Action::make('InstitutionClassImport')
                ->label('Import')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);


                    Excel::import(new LegislatorImport, $file);

                    Notification::make()
                        ->title('Legislators Imported')
                        ->success()
                        ->send();
                })
        ];
    }
}