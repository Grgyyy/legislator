<?php

namespace App\Filament\Resources\CompliantTargetsResource\Pages;

use App\Exports\CompliantTargetExport;
use App\Filament\Resources\CompliantTargetsResource;
use App\Services\NotificationHandler;
use Exception;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ListCompliantTargets extends ListRecords
{
    protected static string $resource = CompliantTargetsResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Action::make('CompliantTargetExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new CompliantTargetExport, now()->format('m-d-Y') . ' - ' . 'Compliant Targets.xlsx');
                    } catch (ValidationException $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Validation failed: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'Spreadsheet error: ' . $e->getMessage());
                    } catch (Exception $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', 'An unexpected error occurred: ' . $e->getMessage());
                    }
                }),
        ];
    }



    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.compliant-targets.index') => 'Compliant Targets',
            'List'
        ];
    }

    protected static ?string $title = 'Compliant Targets';
}
