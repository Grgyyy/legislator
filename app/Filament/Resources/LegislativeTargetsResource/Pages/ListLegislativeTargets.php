<?php

namespace App\Filament\Resources\LegislativeTargetsResource\Pages;

use App\Filament\Resources\LegislativeTargetsResource;
use App\Models\Allocation;
use App\Models\Legislator;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;

class ListLegislativeTargets extends ListRecords
{
    protected static string $resource = LegislativeTargetsResource::class;

    protected static ?string $title = 'Legislators';

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return Legislator::query()
            ->has('allocation.target');
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Legislator Name')
                    ->sortable()
                    ->searchable(),
            ])
            ->recordUrl(
                fn($record) => route('filament.admin.resources.legislative-targets.listAllocation', ['record' => $record->id]),
            )
            ->filters([
                // Define your filters here
            ]);
    }
}
