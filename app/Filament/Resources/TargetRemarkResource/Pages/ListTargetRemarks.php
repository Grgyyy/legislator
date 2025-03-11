<?php

namespace App\Filament\Resources\TargetRemarkResource\Pages;

use App\Exports\RegionExport;
use App\Exports\TargetRemarksExport;
use App\Filament\Resources\TargetRemarkResource;
use App\Services\NotificationHandler;
use Exception;
use Filament\Actions\Action;
use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ListTargetRemarks extends ListRecords
{
    protected static string $resource = TargetRemarkResource::class;

    protected ?string $heading = 'Non-Compliant Target Remark';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.target-remarks.index') => 'Non-Compliant Target Remark',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),

            Action::make('TargetRemarksExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new TargetRemarksExport, now()->format('m-d-Y') . ' - ' . 'Target Remarks.xlsx');
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
