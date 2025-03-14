<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegislativeTargetsResource\Pages;
use App\Models\Legislator;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Gate;

class LegislativeTargetsResource extends Resource
{
    protected static ?string $model = Legislator::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "GENERAL REPORTS";

    protected static ?string $navigationLabel = 'Legislative Targets';

    public static function canViewAny(): bool
    {
        return Gate::allows('view-any-legislative-targets-report');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('view-any-legislative-targets-report');
    }

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
