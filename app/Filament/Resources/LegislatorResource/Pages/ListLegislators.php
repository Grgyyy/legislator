<?php

namespace App\Filament\Resources\LegislatorResource\Pages;

use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\LegislatorResource;
use App\Imports\LegislatorImport;
use App\Models\Legislator;
use Exception;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Builder;
use Filament\Resources\Components\Tab;

class ListLegislators extends ListRecords
{
    protected static string $resource = LegislatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),
            Action::make('LegislatorImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    try {
                        Excel::import(new LegislatorImport, $file);
                        Notification::make()
                            ->title('Import Successful')
                            ->body('Legislator import successful!')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Legislator import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    // public function getTabs(): array
    // {
    //     return [
    //         'All' => Tab::make(),
    //         'Active' => Tab::make()
    //             ->modifyQueryUsing(function ($query) {
    //                 $query->where('status_id', '1');
    //             })
    //             ->badge(function () {
    //                 return Legislator::where('status_id', '1')->count();
    //             }),
    //         'Inactive' => Tab::make()
    //             ->modifyQueryUsing(function ($query) {
    //                 $query->where('status_id', '2');
    //             })
    //             ->badge(function () {
    //                 return Legislator::where('status_id', '2')->count();
    //             }),
    //     ];
    // }
}
