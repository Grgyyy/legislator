<?php

namespace App\Filament\Resources\ToolkitResource\Pages;

use App\Filament\Resources\ToolkitResource;
use App\Imports\ToolkitImport;
use Exception;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use Filament\Forms\Components\FileUpload;

class ListToolkits extends ListRecords
{
    protected static string $resource = ToolkitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New')
            ,

            Action::make('SkillPriorityImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new ToolkitImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'Qualification Title Toolkits data have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the Qualification Title Toolkits data: ' . $e->getMessage());
                    }
                }),
        ];
    }
}
