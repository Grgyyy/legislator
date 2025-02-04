<?php

namespace App\Filament\Resources\CompliantTargetsResource\Pages;

use Exception;
use Filament\Actions\Action;
use App\Exports\NonCompliantExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use App\Exports\CompliantTargetExport;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\CompliantTargetsResource;
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
                        return Excel::download(new CompliantTargetExport, 'compliant_target_export.xlsx');
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
