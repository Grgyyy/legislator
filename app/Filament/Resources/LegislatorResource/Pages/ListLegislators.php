<?php

namespace App\Filament\Resources\LegislatorResource\Pages;

use App\Filament\Resources\LegislatorResource;
use App\Imports\LegislatorImport;
use Filament\Resources\Pages\ListRecords;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ListLegislators extends ListRecords
{
    protected static string $resource = LegislatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('LegislatorImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new LegislatorImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The legislators have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the legislators: ' . $e->getMessage());
                    }
                }),
        ];
    }
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
