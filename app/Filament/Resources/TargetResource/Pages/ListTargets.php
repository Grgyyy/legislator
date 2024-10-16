<?php

namespace App\Filament\Resources\TargetResource\Pages;

use Exception;
use Filament\Actions\Action;
use App\Imports\TargetImport;
use Filament\Actions\CreateAction;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\TargetResource;

class ListTargets extends ListRecords
{
    protected static string $resource = TargetResource::class;

    protected ?string $heading = 'Pending Targets';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.targets.index') => 'Pending Targets',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),

            Action::make('TargetImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new TargetImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The institution class have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the Target data: ' . $e->getMessage());
                    }
                }),
        ];
    }
}
