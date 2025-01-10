<?php

namespace App\Filament\Resources\NonCompliantTargetResource\Pages;

use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use App\Exports\NonCompliantExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use App\Exports\AttributionTargetExport;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Filament\Resources\NonCompliantTargetResource;

class ListNonCompliantTargets extends ListRecords
{
    protected static string $resource = NonCompliantTargetResource::class;

    protected static ?string $title = 'Non-Compliant Targets';


    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make()
            //     ->icon('heroicon-m-plus')
            //     ->label('New'),

            Action::make('NonCompliantExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new NonCompliantExport, 'non_compliant_target_export.xlsx');
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
            route('filament.admin.resources.non-compliant-targets.index') => 'Non-Compliant Targets',
            'List'
        ];
    }
}
