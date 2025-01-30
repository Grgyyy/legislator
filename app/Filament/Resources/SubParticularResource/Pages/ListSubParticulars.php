<?php

namespace App\Filament\Resources\SubParticularResource\Pages;

use App\Filament\Resources\SubParticularResource;
use App\Imports\ParticularTypesImport;
use App\Models\SubParticular;
use Filament\Resources\Pages\ListRecords;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ListSubParticulars extends ListRecords
{
    protected static string $resource = SubParticularResource::class;
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected static ?string $title = 'Particular Types';

    public function getBreadcrumbs(): array
    {
        return [
            '/particular-types' => 'Particular Types',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('RegionImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-down')
                ->form([
                    FileUpload::make('file')
                        ->label('Import District')
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
                            Excel::import(new ParticularTypesImport, $filePath);
                            NotificationHandler::sendSuccessNotification('Import Successful', 'The Particular Types have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the Particular Types: ' . $e->getMessage());
                        } finally {
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
            }),
        ];
    }
}