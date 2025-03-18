<?php

namespace App\Filament\Resources\NonCompliantTargetResource\Pages;

use App\Exports\NonCompliantExport;
use App\Filament\Resources\NonCompliantTargetResource;
use App\Services\NotificationHandler;
use Exception;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ListNonCompliantTargets extends ListRecords
{
    protected static string $resource = NonCompliantTargetResource::class;

    protected static ?string $title = 'Non-Compliant Targets';


    protected function getHeaderActions(): array
    {
        return [
            Action::make('NonCompliantExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-up')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new NonCompliantExport, now()->format('m-d-Y') . ' - ' . 'Non-compliant Targets.xlsx');
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
