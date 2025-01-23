<?php

namespace App\Filament\Resources\PartylistResource\Pages;

use App\Filament\Resources\PartylistResource;
use App\Imports\PartylistImport;
use Filament\Resources\Pages\ListRecords;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ListPartylists extends ListRecords
{
    protected static string $resource = PartylistResource::class;

    protected static ?string $title = 'Party-list';

    public function getBreadcrumbs(): array
    {
        return [
            '/party-lists' => 'Party-lists',
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
                    FileUpload::make('attachment')
                        ->required()
                        ->markAsRequired(false),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new PartylistImport, $file);
                        NotificationHandler::sendSuccessNotification('Import Successful', 'The party-lists have been successfully imported from the file.');
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the party-lists: ' . $e->getMessage());
                    }
                }),
        ];
    }
}