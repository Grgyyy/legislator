<?php

namespace App\Filament\Resources\SkillPriorityResource\Pages;

use Exception;
use Filament\Actions\Action;
use App\Imports\TargetImport;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SkillsPriorityExport;
use App\Imports\SkillsPriorityImport;
use App\Services\NotificationHandler;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\SkillPriorityResource;
use Maatwebsite\Excel\Validators\ValidationException;

class ListSkillPriorities extends ListRecords
{
    protected static string $resource = SkillPriorityResource::class;
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

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
                    FileUpload::make('file')
                        ->label('Import Skill Priorities')
                        ->required()
                        ->markAsRequired(false)
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),
                ])
                ->action(function (array $data) {
                    if (isset($data['file']) && is_string($data['file'])) {
                        $filePath = storage_path('app/' . $data['file']);

                        try {
                            Excel::import(new SkillsPriorityImport, $filePath);
                            NotificationHandler::sendSuccessNotification('Import Successful', 'The Skill Priorities have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the skill priorities: ' . $e->getMessage());
                        } finally {
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                })
                ->visible(fn() => !Auth::user()->hasRole('TESDO')),


            Action::make('SkillsPriorityExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new SkillsPriorityExport, 'skills_priority_export.xlsx');
                    } catch (ValidationException $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Validation failed: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Spreadsheet error: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'An unexpected error occurred: ' . $e->getMessage());
                    };
                }),



        ];
    }
}
