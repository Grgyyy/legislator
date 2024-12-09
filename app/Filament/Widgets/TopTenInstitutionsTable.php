<?php

namespace App\Filament\Widgets;

use App\Models\Tvi;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class TopTenInstitutionsTable extends BaseWidget
{
    public function table(Table $table): Table
    {
        $query = Tvi::query()
            ->join('targets', 'tvis.id', '=', 'targets.tvi_id')
            ->join('target_statuses', 'targets.target_status_id', '=', 'target_statuses.id')
            ->where('target_statuses.desc', '=', 'Compliant')
            ->whereNull('targets.deleted_at')
            ->selectRaw('tvis.id as institution_id, tvis.name as institution_name, SUM(targets.total_amount) AS total_count')
            ->groupBy('tvis.id', 'tvis.name')
            ->orderByDesc('total_count')
            ->limit(10);

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('institution_name')
                    ->label('Institution Name')
                    ->sortable(), 
                TextColumn::make('total_count')
                    ->label('Total Amount')
                    ->sortable(),
            ]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->id;
    }
}