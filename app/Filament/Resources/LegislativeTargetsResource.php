<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegislativeTargetsResource\Pages;
use App\Filament\Resources\LegislativeTargetsResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Legislator;

class LegislativeTargetsResource extends Resource
{
    protected static ?string $model = Legislator::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "GENERAL REPORTS";

    protected static ?string $navigationLabel = 'Legislative Targets';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    // public static function table(Table $table): Table
    // {
    //     return $table
    //         ->columns([
    //             TextColumn::make('name')
    //                 ->label('Legislator')
    //                 ->searchable(),
    //         ])
    //         ->recordUrl(
    //             fn($record) => route('filament.admin.resources.legislative-targets.listAllocation', ['record' => $record->id]),
    //         )
    //         ->filters([
    //             //
    //         ])
    //         ->actions([
    //             // Tables\Actions\EditAction::make(),
    //         ])
    //         ->bulkActions([
    //             // Tables\Actions\BulkActionGroup::make([
    //             //     Tables\Actions\DeleteBulkAction::make(),
    //             // ]),
    //         ]);
    // }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegislativeTargets::route('/'),
            'listAllocation' => Pages\ListAllocation::route('/{record}/allocation'),
            'targetReport' => Pages\TargetReport::route('/{record}/report')
        ];
    }
}
