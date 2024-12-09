<?php

namespace App\Filament\Widgets;

use App\Models\Legislator;
use App\Models\Tvi;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class TopTenLegislatorsTable extends BaseWidget
{
    public function table(Table $table): Table
    {
        $query = Legislator::query()
            ->join('allocations', 'legislators.id', '=', 'allocations.legislator_id')
            ->selectRaw('legislators.id as legislator_id, legislators.name as legislator_name, SUM(allocations.allocation) AS total_allocation')
            ->groupBy('legislators.id', 'legislators.name')
            ->orderByDesc('total_allocation')
            ->limit(10);

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('legislator_name')
                    ->label('Legislator')
                    ->sortable(), 
                TextColumn::make('total_allocation')
                    ->label('Total Allocation')
                    ->sortable(),
            ]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->id;
    }
}