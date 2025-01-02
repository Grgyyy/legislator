<?php

namespace App\Filament\Resources\AttributionTargetResource\Pages;

use App\Filament\Resources\AttributionTargetResource;
use App\Imports\AttributionTargetImport;
use Filament\Actions\Action;
use Exception;
use Filament\Actions\CreateAction;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;

class ListAttributionTargets extends ListRecords
{
    protected static string $resource = AttributionTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New')
            ,

            Action::make('TargetImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new AttributionTargetImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'Target data have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the Target data: ' . $e->getMessage());
                    }
                }),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.attribution-targets.index') => 'Attribution Targets',
            'List'
        ];
    }

    protected static ?string $title = 'Attribution Targets';
}
