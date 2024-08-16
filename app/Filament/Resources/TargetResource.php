<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TargetResource\Pages;
use App\Filament\Resources\TargetResource\RelationManagers;
use App\Models\Province;
use App\Models\Target;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TargetResource extends Resource
{
    protected static ?string $model = Target::class;

    protected static ?string $navigationGroup = "MANAGE TARGET";

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make("legislator_id")
                    ->required()
                    ->relationship("legislator", "name"),
                Select::make("legislator_id")
                    ->required()
                    ->relationship("legislator", "particular")
                    ->label('Particular'),
                Select::make("province_id")
                    ->required()
                    ->relationship("province", "name"),
                Select::make("province_id")
                    ->required()
                    ->relationship("province", "region_id")
                    ->label('Region'),
                Select::make("scholarship_program_id")
                    ->required()
                    ->relationship("scholarship_program", "name"),
                Select::make("tvi_id")
                    ->required()
                    ->relationship("tvi", "name")
                    ->label('Institution'),
                TextInput::make('number_of_slots')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->rules(['min:10', 'max:25'])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No targets yet')
            ->columns([
                TextColumn::make("legislator.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("legislator.particular")
                    ->label('Particular')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("province.name")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("province.region.name")
                    ->label('Region')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("scholarship_program.name")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("tvi.name")
                    ->label('Institution')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->filtersTriggerAction(
                fn (\Filament\Actions\StaticAction $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->trashed()),
                Tables\Actions\DeleteAction::make(), 
                Tables\Actions\RestoreAction::make(), 
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(), 
                    Tables\Actions\RestoreBulkAction::make(), 
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTargets::route('/'),
            'create' => Pages\CreateTarget::route('/create'),
            'edit' => Pages\EditTarget::route('/{record}/edit'),
        ];
    }
}
