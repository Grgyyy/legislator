<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CompliantTargetsResource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopTvis extends BaseWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(CompliantTargetsResource::getEloquentQuery())
            ->defaultPaginationPageOption(5)
            // ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('allocation.particular.subParticular.fundSource.name')
                    ->label('Allocation Type'),
                TextColumn::make('attributionAllocation.legislator.name')
                    ->label('Legislator I'),
                TextColumn::make('allocation.legislator.name')
                    ->label('Legislator II'),
                TextColumn::make('allocation.particular.subParticular.name')
                    ->label('Particular'),
                TextColumn::make('allocation.soft_or_commitment')
                    ->label('Soft/Commitment'),
                TextColumn::make('appropriation_type')
                    ->label('Appropriation Type')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('allocation.year')
                    ->label('Allocation Year')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tvi.district.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tvi.district.municipality.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tvi.district.municipality.province.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tvi.district.municipality.province.region.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tvi.name')
                    ->label('Institution'),
                TextColumn::make('allocation.scholarship_program.name')
                    ->label('Scholarship Program'),
                TextColumn::make('qualification_title.trainingProgram.title')
                    ->label('Qualification Title'),
                TextColumn::make('qualification_title.trainingProgram.priority.name')
                    ->label('Priority Sector'),
                TextColumn::make('qualification_title.trainingProgram.tvet.name')
                    ->label('TVET Sector'),
                TextColumn::make('abdd.name')
                    ->label('ABDD Sector'),
                TextColumn::make('number_of_slots')
                    ->searchable()
                    ->toggleable()
                    ->label('No. of Slots'),
                TextColumn::make('total_amount')
                    ->searchable()
                    ->toggleable()
                    ->label('Total Amount')
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
                TextColumn::make('targetStatus.desc')
                    ->label('Status'),
            ]);
    }
}
