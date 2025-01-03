<?php

namespace App\Filament\Resources\SkillPriorityResource\Pages;

use App\Filament\Resources\SkillPriorityResource;
use App\Imports\SkillsPriorityImport;
use Filament\Resources\Pages\ListRecords;
use Exception;
use Filament\Actions\Action;
use App\Imports\TargetImport;
use Filament\Actions\CreateAction;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use Filament\Forms\Components\FileUpload;

class ListSkillPriorities extends ListRecords
{
    protected static string $resource = SkillPriorityResource::class;

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
                        Excel::import(new SkillsPriorityImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'Skill Priority data have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the Skill Priority data: ' . $e->getMessage());
                    }
                }),
        ];
    }
}
