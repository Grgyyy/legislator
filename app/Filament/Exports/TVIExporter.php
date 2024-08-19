<?php

namespace App\Filament\Exports;

use App\Models\TVI;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;



class TVIExporter extends Exporter
{
    protected static ?string $model = TVI::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('name'),
            ExportColumn::make('district'),
            ExportColumn::make('province_id'),
            ExportColumn::make('municipality_class'),
            ExportColumn::make('tvi_class_id'),
            ExportColumn::make('institution_class_id'),
            ExportColumn::make('address'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your TVI export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
    public function getFileName(Export $export): string
    {
        return 'Institution';
    }

}
