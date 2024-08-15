<?php

namespace App\Filament\Exports;

use App\Models\QualificationTitle;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class QualificationTitleExporter extends Exporter
{
    protected static ?string $model = QualificationTitle::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('code'),
            ExportColumn::make('title'),
            ExportColumn::make('scholarship_program_id'),
            ExportColumn::make('sector_id'),
            ExportColumn::make('duration'),
            ExportColumn::make('training_cost_pcc'),
            ExportColumn::make('cost_of_toolkit_pcc'),
            ExportColumn::make('training_cost_pcc'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your qualification title export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
