<?php

namespace App\Filament\Resources\FundSourceResource\Pages;

use Filament\Actions\Action;
use App\Exports\FundSourceExport;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Log;
use App\Exports\PendingTargetExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationHandler;
use PhpOffice\PhpSpreadsheet\Exception;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\FundSourceResource;
use Maatwebsite\Excel\Validators\ValidationException;

class ListFundSources extends ListRecords
{
    protected static string $resource = FundSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus'),

            Action::make('FundSourceExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (array $data) {
                    try {
                        return Excel::download(new FundSourceExport, 'fund_source_export.xlsx');
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
}
